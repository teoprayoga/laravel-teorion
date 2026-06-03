<?php

namespace Teoprayoga\Teorion\Pipeline;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Teoprayoga\Teorion\Events\QueryAudited;
use Teoprayoga\Teorion\QueryFilter;
use Teoprayoga\Teorion\QueryFingerprint;

/**
 * Wraps a Builder for raw-flow audit. Calls forward to the underlying
 * Builder; on terminal methods (get/first/paginate/cursorPaginate/count)
 * a QueryAudited event is emitted.
 *
 * Usage: Post::query()->filterAudited($request)->where(...)->get()
 */
class AuditedBuilder
{
    private const TERMINALS = ['get', 'first', 'paginate', 'cursorPaginate', 'count'];

    public function __construct(
        private Builder $builder,
        private QueryFilter $filter,
        private Request $request,
        private float $startedAt,
    ) {}

    public function __call(string $method, array $args): mixed
    {
        $result = $this->builder->$method(...$args);

        if (in_array($method, self::TERMINALS, true)) {
            $this->emit($method, $result);

            return $result;
        }

        if ($result instanceof Builder) {
            $this->builder = $result;

            return $this;
        }

        return $result;
    }

    private function emit(string $terminal, mixed $result): void
    {
        if (!config('teorion.audit.enabled', false)) {
            return;
        }

        $fingerprint = (new QueryFingerprint())->make($this->filter, $this->builder, $this->request);

        $rate = (float) config('teorion.audit.sample_rate', 1.0);
        if ($rate <= 0.0) {
            return;
        }
        if ($rate < 1.0) {
            $sample = hexdec(substr($fingerprint->hash, 0, 8)) / 0xFFFFFFFF;
            if ($sample > $rate) {
                return;
            }
        }

        $record = [
            'fingerprint'   => [
                'hash'      => $fingerprint->hash,
                'algorithm' => $fingerprint->algorithm,
                'payload'   => $fingerprint->payload,
            ],
            'filter_class'  => $this->filter::class,
            'model_class'   => $this->builder->getModel()::class,
            'terminal_mode' => $terminal,
            'limit'         => null,
            'result_count'  => $this->countResult($terminal, $result),
            'duration_ms'   => round((microtime(true) - $this->startedAt) * 1000, 2),
            'user_id'       => $this->request->user()?->getAuthIdentifier(),
        ];

        event(new QueryAudited($record));

        if (config('teorion.audit.log', false)) {
            Log::channel(config('teorion.audit.log_channel'))->info('teorion.query_audited', $record);
        }
    }

    private function countResult(string $terminal, mixed $result): int
    {
        return match ($terminal) {
            'first' => $result ? 1 : 0,
            'count' => (int) $result,
            default => is_countable($result)
                ? count($result)
                : (is_object($result) && method_exists($result, 'items') ? count($result->items()) : 0),
        };
    }
}
