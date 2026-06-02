<?php

namespace Teoprayoga\Teorion\Filters;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Inline filter: apply a closure for one-off filter logic that doesn't warrant a custom Filter class.
 *
 * Example:
 *   'has_picture' => new CallbackFilter(
 *       fn(Builder $q, $value) => filter_var($value, FILTER_VALIDATE_BOOLEAN)
 *           ? $q->whereHas('picture')
 *           : $q->whereDoesntHave('picture')
 *   ),
 */
class CallbackFilter extends BaseFilter
{
    public function __construct(private readonly Closure $callback)
    {
        parent::__construct(null);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        return ($this->callback)($query, $value, $param, $request);
    }
}
