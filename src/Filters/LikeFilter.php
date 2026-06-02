<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LikeFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return $query->where($this->resolveColumn($param), 'like', '%' . $value . '%');
    }

    public function validationRule(): string|array
    {
        return 'nullable|string|max:255';
    }
}
