<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\ScopeFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class ScopeFilterTest extends TestCase
{
    public function test_delegates_to_named_scope_on_model(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 200]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 5]);

        $filter  = new ScopeFilter('popular');
        $request = Request::create('/', 'GET', ['view_threshold' => '100']);

        $result = $filter->apply(Post::query(), '_trigger', 'popular', $request)->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_silently_returns_query_when_scope_method_missing(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A']);

        $filter = new ScopeFilter('nonExistentScope');
        $result = $filter->apply(Post::query(), 'any', 'nonExistentScope', new Request())->get();

        // Should not throw — returns all
        $this->assertCount(1, $result);
    }
}
