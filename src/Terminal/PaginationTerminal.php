<?php

namespace Teoprayoga\Teorion\Terminal;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

final class PaginationTerminal
{
    public function execute(Builder $query, Request $request): LengthAwarePaginator|Collection
    {
        $perPageKey     = config('teorion.per_page_key', 'per_page');
        $maxResultsKey  = config('teorion.max_results_key', 'max_results');
        $paginateKey    = config('teorion.paginate_key', 'is_paginate');
        $defaultPerPage = (int) config('teorion.default_per_page', 10);

        $limit = (int) ($request->input($maxResultsKey) ?? $request->input($perPageKey) ?? $defaultPerPage);

        if ($request->boolean($paginateKey)) {
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
}
