<?php

namespace Teoprayoga\Teorion\Traits;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Teoprayoga\Teorion\Events\QueryAudited;
use Teoprayoga\Teorion\Exceptions\QueryFilterNotFoundException;
use Teoprayoga\Teorion\Pipeline\AuditedBuilder;
use Teoprayoga\Teorion\QueryFingerprint;
use Teoprayoga\Teorion\QueryFingerprintResult;
use Teoprayoga\Teorion\QueryFilter;
use Teoprayoga\Teorion\Terminal\PaginationTerminal;

trait Filterable
{
    public function newQueryFilter(): QueryFilter
    {
        if (property_exists($this, 'queryFilter') && isset($this->queryFilter)) {
            return new $this->queryFilter();
        }

        $base      = class_basename(static::class);
        $namespace = config('teorion.query_filters_namespace', 'App\\QueryFilters');
        $class     = trim($namespace, '\\') . '\\' . $base . 'QueryFilter';

        if (class_exists($class)) {
            return new $class();
        }

        throw new QueryFilterNotFoundException(static::class, $class);
    }

    /**
     * Apply all declared filters, scopes, withs, withCounts, and sorts.
     * Returns Builder — chain your own clauses or paginate manually.
     *
     * Usage: Model::query()->filter($request)->paginate(10)
     */
    public function scopeFilter(Builder $query, Request $request): Builder
    {
        return $this->newQueryFilter()->apply($query, $request);
    }

    /**
     * Apply filters and return an audited Builder wrapper.
     * Terminal methods (get, first, paginate, cursorPaginate, count)
     * automatically emit QueryAudited events when audit is enabled.
     *
     * Usage: Post::query()->filterAudited($request)->where(...)->get()
     */
    public function scopeFilterAudited(Builder $query, Request $request): AuditedBuilder
    {
        $startedAt = microtime(true);
        $filter    = $this->newQueryFilter();
        $filtered  = $filter->apply($query, $request);

        return new AuditedBuilder($filtered, $filter, $request, $startedAt);
    }

    /**
     * Apply all filters AND paginate/get in one call.
     * Handles is_paginate, per_page, max_results, makeVisible, makeHidden.
     *
     * Usage: Model::query()->filterAndPaginate($request)
     */
    public function scopeFilterAndPaginate(Builder $query, Request $request): LengthAwarePaginator|CursorPaginator|Collection
    {
        $startedAt = microtime(true);
        $filter    = $this->newQueryFilter();
        $terminal  = new PaginationTerminal();

        $filtered = $filter->apply($query, $request);
        $result   = $terminal->execute($filtered, $request);

        $this->auditFilteredQuery($filter, $query, $request, $terminal, $result, $startedAt);

        return $result;
    }

    /**
     * Apply all filters AND find a single model by id or uuid.
     * Static method (not scope) — required because Eloquent scopes coerce null returns to Builder.
     *
     * Usage: Post::findFiltered($request, $uuidOrId)
     */
    public static function findFiltered(Request $request, int|string $id): ?Model
    {
        $instance  = new static();
        $startedAt = microtime(true);
        $filter    = $instance->newQueryFilter();

        $filtered = $filter->apply($instance->newQuery(), $request);
        $key      = is_numeric($id) ? $instance->getKeyName() : 'uuid';
        $result   = $filtered->where($key, $id)->first();

        $instance->auditFindResult($filter, $instance->newQuery(), $request, $result, $startedAt);

        if ($result === null) {
            return null;
        }

        if (!empty($request->visibles)) {
            $result->makeVisible($request->visibles);
        }
        if (!empty($request->hiddens)) {
            $result->makeHidden($request->hiddens);
        }

        return $result;
    }

    private function auditFilteredQuery(
        QueryFilter $filter,
        Builder $query,
        Request $request,
        PaginationTerminal $terminal,
        LengthAwarePaginator|CursorPaginator|Collection $result,
        float $startedAt,
    ): void {
        if (!config('teorion.audit.enabled', false)) {
            return;
        }

        $fingerprint = (new QueryFingerprint())->make($filter, $query, $request);

        if (!$this->shouldAudit($fingerprint)) {
            return;
        }

        $this->emitAudit([
            'fingerprint'   => [
                'hash'      => $fingerprint->hash,
                'algorithm' => $fingerprint->algorithm,
                'payload'   => $fingerprint->payload,
            ],
            'filter_class'  => $filter::class,
            'model_class'   => $query->getModel()::class,
            'terminal_mode' => $terminal->resolveMode($request),
            'limit'         => $terminal->resolveLimit($request),
            'result_count'  => $this->resultCount($result),
            'duration_ms'   => round((microtime(true) - $startedAt) * 1000, 2),
            'user_id'       => $request->user()?->getAuthIdentifier(),
        ]);
    }

    private function auditFindResult(
        QueryFilter $filter,
        Builder $query,
        Request $request,
        ?Model $result,
        float $startedAt,
    ): void {
        if (!config('teorion.audit.enabled', false)) {
            return;
        }

        $fingerprint = (new QueryFingerprint())->make($filter, $query, $request);

        if (!$this->shouldAudit($fingerprint)) {
            return;
        }

        $this->emitAudit([
            'fingerprint'   => [
                'hash'      => $fingerprint->hash,
                'algorithm' => $fingerprint->algorithm,
                'payload'   => $fingerprint->payload,
            ],
            'filter_class'  => $filter::class,
            'model_class'   => $query->getModel()::class,
            'terminal_mode' => 'find',
            'limit'         => 1,
            'result_count'  => $result ? 1 : 0,
            'duration_ms'   => round((microtime(true) - $startedAt) * 1000, 2),
            'user_id'       => $request->user()?->getAuthIdentifier(),
        ]);
    }

    /**
     * Deterministic per-fingerprint sampling — same query intent always
     * yields same audit decision (consistent with cache-key derivation).
     * Caller must short-circuit on `audit.enabled` before calling this.
     */
    private function shouldAudit(QueryFingerprintResult $fingerprint): bool
    {
        $rate = (float) config('teorion.audit.sample_rate', 1.0);

        if ($rate >= 1.0) {
            return true;
        }
        if ($rate <= 0.0) {
            return false;
        }

        $sample = hexdec(substr($fingerprint->hash, 0, 8)) / 0xFFFFFFFF;

        return $sample <= $rate;
    }

    private function emitAudit(array $record): void
    {
        event(new QueryAudited($record));

        if (config('teorion.audit.log', false)) {
            Log::channel(config('teorion.audit.log_channel'))->info('teorion.query_audited', $record);
        }
    }

    private function resultCount(LengthAwarePaginator|CursorPaginator|Collection $result): int
    {
        if ($result instanceof Collection) {
            return $result->count();
        }

        return count($result->items());
    }
}
