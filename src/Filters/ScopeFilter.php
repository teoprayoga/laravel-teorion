<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Bridge filter: delegates to an existing scopeXxx() method on the model.
 * Use for complex filters (whereHas chains, spatial, role-based) that can't
 * be expressed as a simple built-in filter type.
 */
class ScopeFilter extends BaseFilter
{
    public function __construct(private readonly string $scopeName)
    {
        parent::__construct(null);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $method = $this->scopeName;

        if (method_exists($query->getModel(), 'scope' . ucfirst($method))) {
            return $query->$method($request);
        }

        return $query;
    }
}
