<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Filter by presence/absence of a relation.
 *
 * Value truthy → whereHas(relation)
 * Value falsy  → whereDoesntHave(relation)
 *
 * Example:
 *   'has_comments' => new HasFilter('comments')
 *   → ?has_comments=1   → posts WITH comments
 *   → ?has_comments=0   → posts WITHOUT comments
 */
class HasFilter extends BaseFilter
{
    public function __construct(private readonly string $relation)
    {
        parent::__construct(null);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN)
            ? $query->whereHas($this->relation)
            : $query->whereDoesntHave($this->relation);
    }

    public function validationRule(): string|array
    {
        return 'nullable|in:0,1,true,false';
    }
}
