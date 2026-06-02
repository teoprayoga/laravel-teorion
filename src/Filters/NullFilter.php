<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * value truthy (1, "1", true)  → WHERE col IS NOT NULL
 * value falsy  (0, "0", false) → WHERE col IS NULL
 */
class NullFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $col = $this->resolveColumn($param);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $query->whereNotNull($col)
            : $query->whereNull($col);
    }

    public function validationRule(): string|array
    {
        return 'nullable|in:0,1,true,false';
    }
}
