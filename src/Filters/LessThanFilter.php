<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class LessThanFilter extends BaseFilter
{
    public function __construct(?string $column = null, private readonly bool $orEqual = false)
    {
        parent::__construct($column);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return $query->where($this->resolveColumn($param), $this->orEqual ? '<=' : '<', $value);
    }
}
