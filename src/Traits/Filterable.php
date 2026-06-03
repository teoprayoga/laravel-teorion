<?php

namespace Teoprayoga\Teorion\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\QueryFilterNotFoundException;
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
     * Apply all filters AND paginate/get in one call.
     * Handles is_paginate, per_page, max_results, makeVisible, makeHidden.
     *
     * Usage: Model::query()->filterAndPaginate($request)
     */
    public function scopeFilterAndPaginate(Builder $query, Request $request): LengthAwarePaginator|Collection
    {
        $filtered = $this->newQueryFilter()->apply($query, $request);

        return (new PaginationTerminal())->execute($filtered, $request);
    }

    /**
     * Apply all filters AND find a single model by id or uuid.
     * Static method (not scope) — required because Eloquent scopes coerce null returns to Builder.
     *
     * Usage: Post::findFiltered($request, $uuidOrId)
     */
    public static function findFiltered(Request $request, int|string $id): ?Model
    {
        $instance = new static();
        $filtered = $instance->newQueryFilter()->apply($instance->newQuery(), $request);

        $key    = is_numeric($id) ? $instance->getKeyName() : 'uuid';
        $result = $filtered->where($key, $id)->first();

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
}
