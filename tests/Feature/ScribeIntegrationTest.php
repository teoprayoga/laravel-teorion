<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Teoprayoga\Teorion\Scribe\QueryFilterDocsExtractor;
use Teoprayoga\Teorion\Scribe\Strategies\UsesQueryFilterStrategy;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\Fixtures\SampleController;
use Teoprayoga\Teorion\Tests\TestCase;

class ScribeIntegrationTest extends TestCase
{
    public function test_extractor_returns_params_from_query_filter(): void
    {
        $extractor = new QueryFilterDocsExtractor();
        $params    = $extractor->extract(PostQueryFilter::class);

        // From filters()
        $this->assertArrayHasKey('search',     $params);
        $this->assertArrayHasKey('title',      $params);
        $this->assertArrayHasKey('status',     $params);
        $this->assertArrayHasKey('is_active',  $params);

        // From allowedSorts/withs/withCounts/scopes
        $this->assertArrayHasKey('sort',         $params);
        $this->assertArrayHasKey('withs[]',      $params);
        $this->assertArrayHasKey('withCounts[]', $params);
        $this->assertArrayHasKey('scopes[]',     $params);

        // Pagination meta
        $this->assertArrayHasKey('is_paginate', $params);
        $this->assertArrayHasKey('per_page',    $params);
    }

    public function test_extractor_correctly_describes_filter_metadata(): void
    {
        $extractor = new QueryFilterDocsExtractor();
        $params    = $extractor->extract(PostQueryFilter::class);

        $this->assertSame('string', $params['search']['type']);
        $this->assertFalse($params['search']['required']);
        $this->assertTrue($params['search']['nullable']);

        $this->assertSame('boolean', $params['is_active']['type']);

        $this->assertStringContainsString('created_at', $params['sort']['description']);
        $this->assertStringContainsString('comments', $params['withs[]']['description']);
    }

    public function test_strategy_returns_empty_for_methods_without_attribute(): void
    {
        $strategy = new UsesQueryFilterStrategy();
        $result   = $strategy([
            'controller' => SampleController::class,
            'method'     => 'show',
        ]);

        $this->assertSame([], $result);
    }

    public function test_strategy_returns_params_for_method_with_attribute(): void
    {
        $strategy = new UsesQueryFilterStrategy();
        $result   = $strategy([
            'controller' => SampleController::class,
            'method'     => 'index',
        ]);

        $this->assertNotEmpty($result);
        $this->assertArrayHasKey('search', $result);
        $this->assertArrayHasKey('sort',   $result);
    }
}
