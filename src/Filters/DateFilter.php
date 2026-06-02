<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DateFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return $query->whereDate($this->resolveColumn($param), $value);
    }

    public function validationRule(): string|array
    {
        return 'nullable|date';
    }
}
