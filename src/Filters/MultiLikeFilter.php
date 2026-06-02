<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class MultiLikeFilter extends BaseFilter
{
    public function __construct(private readonly array $columns)
    {
        parent::__construct(null);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $search = '%' . $value . '%';

        return $query->where(function (Builder $q) use ($search) {
            foreach ($this->columns as $index => $column) {
                $method = $index === 0 ? 'where' : 'orWhere';
                $q->$method($column, 'like', $search);
            }
        });
    }

    public function validationRule(): string|array
    {
        return 'nullable|string|max:255';
    }
}
