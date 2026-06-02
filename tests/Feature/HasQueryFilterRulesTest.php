<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Teoprayoga\Teorion\Concerns\HasQueryFilterRules;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class HasQueryFilterRulesTest extends TestCase
{
    public function test_generates_rules_from_query_filter(): void
    {
        $request = new class extends FormRequest {
            use HasQueryFilterRules;
            protected string $queryFilter = PostQueryFilter::class;

            public function rules(): array
            {
                return $this->queryFilterRules();
            }
        };

        $rules = $request->rules();

        // From PostQueryFilter::filters()
        $this->assertArrayHasKey('search', $rules);       // MultiLikeFilter
        $this->assertArrayHasKey('title', $rules);        // LikeFilter
        $this->assertArrayHasKey('status', $rules);       // ExactFilter
        $this->assertArrayHasKey('is_active', $rules);    // BooleanFilter
        $this->assertArrayHasKey('is_private', $rules);   // BooleanFilter

        // Specific rule values
        $this->assertSame('nullable|string|max:255', $rules['search']);
        $this->assertSame('nullable|string|max:255', $rules['title']);
        $this->assertSame('nullable|in:0,1,true,false', $rules['is_active']);
    }

    public function test_includes_base_rules_for_meta_params(): void
    {
        $request = new class extends FormRequest {
            use HasQueryFilterRules;
            protected string $queryFilter = PostQueryFilter::class;

            public function rules(): array
            {
                return $this->queryFilterRules();
            }
        };

        $rules = $request->rules();

        $this->assertArrayHasKey('sort', $rules);
        $this->assertArrayHasKey('withs', $rules);
        $this->assertArrayHasKey('scopes', $rules);
        $this->assertArrayHasKey('is_paginate', $rules);
        $this->assertArrayHasKey('per_page', $rules);
        $this->assertArrayHasKey('with_trashed', $rules);
    }

    public function test_works_without_query_filter_property(): void
    {
        $request = new class extends FormRequest {
            use HasQueryFilterRules;

            public function rules(): array
            {
                return $this->queryFilterRules();
            }
        };

        $rules = $request->rules();

        // Should still have base rules
        $this->assertArrayHasKey('sort', $rules);
        $this->assertArrayHasKey('is_paginate', $rules);
    }
}
