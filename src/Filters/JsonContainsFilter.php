<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * WHERE JSON_CONTAINS(col, value) — useful for filtering JSON columns containing a value.
 */
class JsonContainsFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return $query->whereJsonContains($this->resolveColumn($param), $value);
    }
}
