<?php

namespace Teoprayoga\Teorion;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Contracts\QueryFilterContract;
use Teoprayoga\Teorion\Pipeline\FilterPipeline;

abstract class QueryFilter implements QueryFilterContract
{
    /**
     * Default sort, applied when client does not provide a sort directive.
     * Use Spatie-style: '-column' = desc, 'column' = asc.
     */
    protected array $defaultSort = [];

    /**
     * If false, disables ?with_trashed and ?only_trashed handling
     * even when the model uses SoftDeletes.
     */
    protected bool $allowTrashedFilters = true;

    /**
     * Declare filters: ['request_param' => FilterInstance].
     * Each filter is applied only when its param is present in the request.
     *
     * Available built-in types:
     *   ExactFilter, LikeFilter, MultiLikeFilter, DateFilter, DateRangeFilter,
     *   BooleanFilter, NullFilter, EnumFilter, InFilter, ScopeFilter, CallbackFilter
     */
    abstract public function filters(): array;

    /**
     * Whitelist of Eloquent scope names callable via scopes[] request param.
     * Maps to scope{Name}() methods on the model.
     */
    public function allowedScopes(): array
    {
        return [];
    }

    /**
     * Whitelist of relation names eager-loadable via withs[] request param.
     */
    public function allowedWiths(): array
    {
        return [];
    }

    /**
     * Whitelist of relation names countable via withCounts[] request param.
     */
    public function allowedWithCounts(): array
    {
        return [];
    }

    /**
     * Whitelist of columns sortable via ?sort=... or ?order_by=... request param.
     */
    public function allowedSorts(): array
    {
        return [];
    }

    /**
     * Whitelist of aggregate operations.
     * Format: ['relation' => ['sum' => ['col1', 'col2'], 'avg' => [...], 'count' => true, 'max' => [...], 'min' => [...]]]
     */
    public function allowedAggregates(): array
    {
        return [];
    }

    public function defaultSort(): array
    {
        return $this->defaultSort;
    }

    public function allowTrashedFilters(): bool
    {
        return $this->allowTrashedFilters;
    }

    final public function apply(Builder $query, Request $request): Builder
    {
        return (new FilterPipeline($this, $request))->run($query);
    }
}
