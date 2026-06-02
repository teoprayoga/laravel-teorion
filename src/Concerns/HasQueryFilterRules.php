<?php

namespace Teoprayoga\Teorion\Concerns;

use Teoprayoga\Teorion\QueryFilter;

/**
 * Mix into a FormRequest to auto-generate validation rules from a QueryFilter.
 *
 * Usage:
 *   class GetRequest extends FormRequest
 *   {
 *       use HasQueryFilterRules;
 *       protected string $queryFilter = ContentQueryFilter::class;
 *
 *       public function rules(): array
 *       {
 *           return array_merge(
 *               $this->queryFilterRules(),
 *               ['class_level_id' => 'nullable|integer|exists:class_levels,id'],
 *           );
 *       }
 *   }
 */
trait HasQueryFilterRules
{
    protected function queryFilterRules(): array
    {
        if (!isset($this->queryFilter) || !is_string($this->queryFilter)) {
            return $this->teorionBaseRules();
        }

        /** @var QueryFilter $filter */
        $filter = new ($this->queryFilter)();

        $rules = $this->teorionBaseRules();

        foreach ($filter->filters() as $param => $filterInstance) {
            $rule = $filterInstance->validationRule();
            $rules[$param] = $rule;
        }

        // withs/withCounts/sort allowed values used only for documentation;
        // here we just ensure the request-shape is valid.
        return $rules;
    }

    private function teorionBaseRules(): array
    {
        return [
            'sort'              => 'nullable|string',
            'order_by'          => 'nullable|string',
            'order_direction'   => 'nullable|in:asc,desc',
            'order'             => 'nullable|array',
            'order.*.by'        => 'required_with:order|string',
            'order.*.direction' => 'nullable|in:asc,desc',
            'order_null_bottoms'   => 'nullable|array',
            'order_null_bottoms.*' => 'string',
            'withs'             => 'nullable|array',
            'withs.*'           => 'string',
            'withCounts'        => 'nullable|array',
            'withCounts.*'      => 'string',
            'scopes'            => 'nullable|array',
            'visibles'          => 'nullable|array',
            'visibles.*'        => 'string',
            'hiddens'           => 'nullable|array',
            'hiddens.*'         => 'string',
            'is_paginate'       => 'nullable|in:0,1,true,false',
            'per_page'          => 'nullable|integer|min:1|max:100',
            'max_results'       => 'nullable|integer|min:1|max:100',
            'with_trashed'      => 'nullable|in:0,1,true,false',
            'only_trashed'      => 'nullable|in:0,1,true,false',
        ];
    }
}
