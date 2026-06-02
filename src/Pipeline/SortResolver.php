<?php

namespace Teoprayoga\Teorion\Pipeline;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\DisallowedSortException;
use Teoprayoga\Teorion\QueryFilter;

final class SortResolver
{
    public function resolve(Builder $query, Request $request, QueryFilter $filter): Builder
    {
        $sorts = $this->parse($request);

        // Apply default sort if no client-provided sort
        if (empty($sorts)) {
            $sorts = $this->parseDefault($filter->defaultSort());
        }

        // Apply null bottoms (NULLs at the end)
        $nullBottoms = $request->input('order_null_bottoms', []);
        if (!empty($nullBottoms) && is_array($nullBottoms)) {
            foreach ($nullBottoms as $col) {
                $query->orderByRaw("CASE WHEN {$col} IS NULL THEN 1 ELSE 0 END");
            }
        }

        $allowed = $filter->allowedSorts();

        foreach ($sorts as $column => $direction) {
            if (!in_array($column, $allowed, true)) {
                throw new DisallowedSortException($column);
            }

            // Custom sort method on the filter class (e.g. sortByUserName)
            $methodName = 'sortBy' . str_replace('_', '', ucwords($column, '_'));
            if (method_exists($filter, $methodName)) {
                $query = $filter->{$methodName}($query, $direction);
                continue;
            }

            $query = $query->orderBy($column, $direction);
        }

        return $query;
    }

    /**
     * Parse sort directives from request, supporting multiple formats:
     *
     * A) Spatie-style: ?sort=-created_at,name
     * B) Legacy single: ?order_by=created_at&order_direction=desc
     * C) Legacy array:  ?order[0][by]=created_at&order[0][direction]=desc
     *
     * Returns: ['column' => 'asc'|'desc']
     */
    public function parse(Request $request): array
    {
        // Format A — Spatie-style
        if ($request->filled('sort')) {
            return $this->parseSpatieStyle((string) $request->input('sort'));
        }

        // Format C — Legacy array
        if (is_array($request->input('order'))) {
            $result = [];
            foreach ($request->input('order') as $entry) {
                if (!is_array($entry) || empty($entry['by'])) {
                    continue;
                }
                $direction = strtolower($entry['direction'] ?? 'asc');
                $result[$entry['by']] = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';
            }
            return $result;
        }

        // Format B — Legacy single
        if ($request->filled('order_by')) {
            $direction = strtolower((string) $request->input('order_direction', 'asc'));
            return [
                $request->input('order_by') => in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc',
            ];
        }

        return [];
    }

    private function parseSpatieStyle(string $sortString): array
    {
        $result = [];

        foreach (explode(',', $sortString) as $column) {
            $column = trim($column);
            if ($column === '') {
                continue;
            }

            if (str_starts_with($column, '-')) {
                $result[ltrim($column, '-')] = 'desc';
            } else {
                $result[$column] = 'asc';
            }
        }

        return $result;
    }

    private function parseDefault(array $defaultSort): array
    {
        $result = [];
        foreach ($defaultSort as $column) {
            if (str_starts_with($column, '-')) {
                $result[ltrim($column, '-')] = 'desc';
            } else {
                $result[$column] = 'asc';
            }
        }
        return $result;
    }
}
