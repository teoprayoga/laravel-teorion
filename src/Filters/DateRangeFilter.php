<?php

namespace Teoprayoga\Teorion\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DateRangeFilter extends BaseFilter
{
    public function __construct(
        ?string $column = null,
        private readonly string $fromParam = 'start_date',
        private readonly string $toParam = 'end_date',
    ) {
        parent::__construct($column);
    }

    public function apply(Builder $query, mixed $value, string $param, Request $request): Builder
    {
        $col   = $this->resolveColumn($param);
        $from  = $request->input($this->fromParam);
        $to    = $request->input($this->toParam);

        if ($from && $to) {
            return $query->whereBetween($col, [$from, $to]);
        }

        if ($from) {
            return $query->whereDate($col, '>=', $from);
        }

        if ($to) {
            return $query->whereDate($col, '<=', $to);
        }

        return $query;
    }

    public function validationRule(): string|array
    {
        return 'nullable|date';
    }
}
