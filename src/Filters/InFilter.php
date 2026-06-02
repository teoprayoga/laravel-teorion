<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class InFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return $query->whereIn($this->resolveColumn($param), array_filter($values));
    }

    public function validationRule(): string|array
    {
        return 'nullable';
    }
}
