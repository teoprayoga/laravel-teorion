<?php

namespace Teoprayoga\Teorion\Pipeline;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\DisallowedWithException;
use Teoprayoga\Teorion\Exceptions\RequiredFilterMissingException;
use Teoprayoga\Teorion\Filters\BaseFilter;
use Teoprayoga\Teorion\QueryFilter;

final class FilterPipeline
{
    public function __construct(
        private readonly QueryFilter $declaration,
        private readonly Request $request,
    ) {}

    public function run(Builder $query): Builder
    {
        $query = $this->applySoftDeletes($query);
        $query = $this->applyFilters($query);
        $query = $this->applyScopes($query);
        $query = $this->applyWiths($query);
        $query = $this->applyWithCounts($query);
        $query = $this->applyAggregates($query);
        $query = $this->applySorts($query);

        return $query;
    }

    private function applySoftDeletes(Builder $query): Builder
    {
        if (!$this->declaration->allowTrashedFilters()) {
            return $query;
        }

        $model = $query->getModel();
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);

        if (!$usesSoftDeletes) {
            return $query;
        }

        if ($this->request->boolean('only_trashed')) {
            return $query->onlyTrashed();
        }

        if ($this->request->boolean('with_trashed')) {
            return $query->withTrashed();
        }

        return $query;
    }

    private function applyFilters(Builder $query): Builder
    {
        foreach ($this->declaration->filters() as $param => $filter) {
            if (!$filter instanceof BaseFilter) {
                continue;
            }

            $readKey = $filter->readKey($param);
            $value   = $this->request->input($readKey);

            $present = $value !== null && $value !== '';

            if (!$present) {
                if ($filter->isRequired()) {
                    throw new RequiredFilterMissingException($readKey);
                }
                if ($filter->hasDefault()) {
                    $value = $filter->getDefault();
                } else {
                    continue;
                }
            }

            $query = $filter->apply($query, $value, $param, $this->request);
        }

        return $query;
    }

    private function applyScopes(Builder $query): Builder
    {
        $scopes = $this->request->input('scopes');

        if (empty($scopes) || !is_array($scopes)) {
            return $query;
        }

        return (new ScopeResolver())->resolve(
            $query,
            $scopes,
            $this->declaration->allowedScopes(),
            $this->request,
        );
    }

    private function applyWiths(Builder $query): Builder
    {
        $withs = $this->request->input('withs');

        if (empty($withs) || !is_array($withs)) {
            return $query;
        }

        $allowed = $this->declaration->allowedWiths();
        $toLoad  = [];

        foreach ($withs as $with) {
            $root = strtok(explode('.', $with)[0], ':');

            if (!in_array($root, $allowed, true)) {
                if (config('teorion.strict_mode', false)) {
                    throw new DisallowedWithException($root);
                }
                continue;
            }

            $toLoad[] = $with;
        }

        return empty($toLoad) ? $query : $query->with($toLoad);
    }

    private function applyWithCounts(Builder $query): Builder
    {
        $withCounts = $this->request->input('withCounts');

        if (empty($withCounts) || !is_array($withCounts)) {
            return $query;
        }

        $allowed = $this->declaration->allowedWithCounts();
        $toCount = array_filter($withCounts, fn($c) => in_array($c, $allowed, true));

        return empty($toCount) ? $query : $query->withCount($toCount);
    }

    private function applyAggregates(Builder $query): Builder
    {
        $aggregates = $this->request->input('withAggregates');

        if (empty($aggregates) || !is_array($aggregates)) {
            return $query;
        }

        $allowed = $this->declaration->allowedAggregates();

        foreach ($aggregates as $relation => $operations) {
            if (!isset($allowed[$relation]) || !is_array($operations)) {
                continue;
            }

            foreach ($operations as $type => $columns) {
                $type = strtolower($type);

                if (!array_key_exists($type, $allowed[$relation])) {
                    continue;
                }

                if ($type === 'count') {
                    $query = $query->withCount($relation);
                    continue;
                }

                $allowedColumns = $allowed[$relation][$type] ?? [];
                $columns = is_array($columns) ? $columns : [$columns];

                foreach ($columns as $col) {
                    if (!in_array($col, $allowedColumns, true)) {
                        continue;
                    }
                    $method = 'with' . ucfirst($type);
                    if (method_exists($query, $method)) {
                        $query = $query->$method($relation, $col);
                    }
                }
            }
        }

        return $query;
    }

    private function applySorts(Builder $query): Builder
    {
        return (new SortResolver())->resolve($query, $this->request, $this->declaration);
    }
}
