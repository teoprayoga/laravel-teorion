<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * WHERE col >= ? AND col <= ?, reading from {param}_min and {param}_max.
 *
 * Example:
 *   'view_count' => new RangeFilter('view_count')
 *   → ?view_count_min=10&view_count_max=100
 */
class RangeFilter extends BaseFilter
{
    public function __construct(
        ?string $column = null,
        private readonly string $minSuffix = '_min',
        private readonly string $maxSuffix = '_max',
    ) {
        parent::__construct($column);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $col = $this->resolveColumn($param);
        $min = $request->input($param . $this->minSuffix);
        $max = $request->input($param . $this->maxSuffix);

        if ($min !== null && $min !== '') {
            $query->where($col, '>=', $min);
        }

        if ($max !== null && $max !== '') {
            $query->where($col, '<=', $max);
        }

        return $query;
    }

    public function validationRule(): string|array
    {
        return 'nullable';
    }
}
