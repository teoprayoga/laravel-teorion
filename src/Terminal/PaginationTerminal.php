<?php

namespace Teoprayoga\Teorion\Terminal;

use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

final class PaginationTerminal
{
    public function execute(Builder $query, Request $request): LengthAwarePaginator|CursorPaginator|Collection
    {
        $maxResultsKey  = config('teorion.max_results_key', 'max_results');
        $paginateKey    = config('teorion.paginate_key', 'is_paginate');
        $perPageKey     = config('teorion.per_page_key', 'per_page');

        $limit = $this->resolveLimit($request);

        if ($this->isCursorPagination($request)) {
            $result = $query->cursorPaginate(
                $limit,
                ['*'],
                config('teorion.cursor_name', 'cursor')
            );
        } elseif ($request->boolean($paginateKey)) {
            $result = $query->paginate($limit);
        } else {
            if ($request->has($maxResultsKey) || $request->has($perPageKey)) {
                $query = $query->limit($limit);
            }
            $result = $query->get();
        }

        if (!empty($request->visibles)) {
            $result->makeVisible($request->visibles);
        }

        if (!empty($request->hiddens)) {
            $result->makeHidden($request->hiddens);
        }

        return $result;
    }

    public function resolveLimit(Request $request): int
    {
        $perPageKey     = config('teorion.per_page_key', 'per_page');
        $maxResultsKey  = config('teorion.max_results_key', 'max_results');
        $defaultPerPage = (int) config('teorion.default_per_page', 10);

        return (int) ($request->input($maxResultsKey) ?? $request->input($perPageKey) ?? $defaultPerPage);
    }

    public function resolveMode(Request $request): string
    {
        if ($this->isCursorPagination($request)) {
            return 'cursor';
        }

        if ($request->boolean(config('teorion.paginate_key', 'is_paginate'))) {
            return 'paginate';
        }

        return 'collection';
    }

    private function isCursorPagination(Request $request): bool
    {
        return $request->input(config('teorion.pagination_mode_key', 'pagination'))
            === config('teorion.cursor_pagination_value', 'cursor');
    }
}
