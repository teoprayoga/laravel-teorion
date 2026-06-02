<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * WHERE col BETWEEN ? AND ?
 *
 * Value can be:
 *   - Array: [from, to]
 *   - Comma-separated string: "10,100"
 */
class BetweenFilter extends BaseFilter
{
    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $bounds = is_array($value) ? $value : explode(',', (string) $value, 2);

        if (count($bounds) !== 2) {
            return $query;
        }

        return $query->whereBetween($this->resolveColumn($param), [$bounds[0], $bounds[1]]);
    }

    public function validationRule(): string|array
    {
        return 'nullable';
    }
}
