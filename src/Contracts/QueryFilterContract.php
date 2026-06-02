<?php

namespace Teoprayoga\Teorion\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

interface QueryFilterContract
{
    public function filters(): array;

    public function allowedScopes(): array;

    public function allowedWiths(): array;

    public function allowedWithCounts(): array;

    public function allowedSorts(): array;

    public function allowedAggregates(): array;

    public function defaultSort(): array;

    public function allowTrashedFilters(): bool;

    public function apply(Builder $query, Request $request): Builder;
}
