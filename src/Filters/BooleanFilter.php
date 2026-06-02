<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BooleanFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return $query->where($this->resolveColumn($param), filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0);
    }

    public function validationRule(): string|array
    {
        return 'nullable|in:0,1,true,false';
    }
}
