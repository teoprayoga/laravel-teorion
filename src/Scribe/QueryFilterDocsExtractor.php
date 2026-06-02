<?php

namespace Teoprayoga\Teorion\Scribe;

use Teoprayoga\Teorion\Filters\BaseFilter;
use Teoprayoga\Teorion\QueryFilter;

/**
 * Extract query parameter documentation from a QueryFilter class.
 * Format matches Scribe's expected schema:
 *
 *   [
 *     'param_name' => [
 *         'name'        => 'param_name',
 *         'type'        => 'string',
 *         'description' => 'Allowed: foo, bar',
 *         'required'    => false,
 *         'example'     => null,
 *         'nullable'    => true,
 *         'enumValues'  => [],
 *     ],
 *   ]
 */
class QueryFilterDocsExtractor
{
    public function extract(string $filterClass): array
    {
        if (!is_subclass_of($filterClass, QueryFilter::class)) {
            return [];
        }

        /** @var QueryFilter $filter */
        $filter = new $filterClass();
        $params = [];

        // Filters
        foreach ($filter->filters() as $key => $filterInstance) {
            if (!$filterInstance instanceof BaseFilter) {
                continue;
            }

            $readKey = $filterInstance->readKey($key);
            $params[$readKey] = [
                'name'        => $readKey,
                'type'        => $this->guessType($filterInstance),
                'description' => $this->describeFilter($filterInstance, $key),
                'required'    => $filterInstance->isRequired(),
                'example'     => null,
                'nullable'    => !$filterInstance->isRequired(),
                'enumValues'  => [],
            ];
        }

        // Sort
        if (!empty($filter->allowedSorts())) {
            $params['sort'] = [
                'name'        => 'sort',
                'type'        => 'string',
                'description' => 'Comma-separated. Prefix "-" for desc. Allowed: ' . implode(', ', $filter->allowedSorts()),
                'required'    => false,
                'example'     => '-' . $filter->allowedSorts()[0],
                'nullable'    => true,
                'enumValues'  => [],
            ];
        }

        // Withs
        if (!empty($filter->allowedWiths())) {
            $params['withs[]'] = [
                'name'        => 'withs[]',
                'type'        => 'string[]',
                'description' => 'Eager-load relations. Allowed: ' . implode(', ', $filter->allowedWiths()),
                'required'    => false,
                'example'     => null,
                'nullable'    => true,
                'enumValues'  => $filter->allowedWiths(),
            ];
        }

        // WithCounts
        if (!empty($filter->allowedWithCounts())) {
            $params['withCounts[]'] = [
                'name'        => 'withCounts[]',
                'type'        => 'string[]',
                'description' => 'Count relations. Allowed: ' . implode(', ', $filter->allowedWithCounts()),
                'required'    => false,
                'example'     => null,
                'nullable'    => true,
                'enumValues'  => $filter->allowedWithCounts(),
            ];
        }

        // Scopes
        if (!empty($filter->allowedScopes())) {
            $params['scopes[]'] = [
                'name'        => 'scopes[]',
                'type'        => 'string[]',
                'description' => 'Apply named query scopes. Allowed: ' . implode(', ', $filter->allowedScopes()),
                'required'    => false,
                'example'     => null,
                'nullable'    => true,
                'enumValues'  => $filter->allowedScopes(),
            ];
        }

        // Pagination meta
        $params['is_paginate'] = [
            'name'        => 'is_paginate',
            'type'        => 'boolean',
            'description' => 'When true, returns a paginated response. Default: false (returns collection).',
            'required'    => false,
            'example'     => 1,
            'nullable'    => true,
            'enumValues'  => [],
        ];

        $params['per_page'] = [
            'name'        => 'per_page',
            'type'        => 'integer',
            'description' => 'Items per page when paginated. Default: ' . config('teorion.default_per_page', 10),
            'required'    => false,
            'example'     => 10,
            'nullable'    => true,
            'enumValues'  => [],
        ];

        return $params;
    }

    private function guessType(BaseFilter $filter): string
    {
        $rule = $filter->validationRule();
        $ruleString = is_array($rule) ? implode('|', $rule) : (string) $rule;

        if (str_contains($ruleString, 'integer')) return 'integer';
        if (str_contains($ruleString, 'date'))    return 'date';
        if (str_contains($ruleString, 'boolean')) return 'boolean';
        if (str_contains($ruleString, '0,1,true,false')) return 'boolean';

        return 'string';
    }

    private function describeFilter(BaseFilter $filter, string $declaredKey): string
    {
        $shortClass = (new \ReflectionClass($filter))->getShortName();
        $desc       = "Filter via {$shortClass} on field '{$declaredKey}'.";

        if ($filter->hasDefault()) {
            $default = is_scalar($filter->getDefault()) ? (string) $filter->getDefault() : 'complex';
            $desc   .= " Default: {$default}.";
        }

        if ($filter->isRequired()) {
            $desc .= ' Required.';
        }

        return $desc;
    }
}
