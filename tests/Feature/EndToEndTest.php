<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class EndToEndTest extends TestCase
{
    public function test_filter_and_paginate_combines_filters_scopes_sorts(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'Alpha', 'status' => 'published', 'view_count' => 200]);
        Post::create(['uuid' => 'b', 'title' => 'Beta',  'status' => 'published', 'view_count' => 50]);
        Post::create(['uuid' => 'c', 'title' => 'Gamma', 'status' => 'draft',     'view_count' => 300]);

        $request = Request::create('/', 'GET', [
            'status'       => 'published',
            'is_paginate'  => '1',
            'per_page'     => '10',
            'sort'         => '-view_count',
            'scopes'       => [['name' => 'popular', 'params' => ['view_threshold' => 100]]],
        ]);

        $result = Post::query()->filterAndPaginate($request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(1, $result->total());
        $this->assertSame('Alpha', $result->items()[0]->title);
    }

    public function test_find_filtered_returns_single_model_by_uuid(): void
    {
        Post::create(['uuid' => 'target-uuid', 'title' => 'Target']);
        Post::create(['uuid' => 'other-uuid',  'title' => 'Other']);

        $result = Post::findFiltered(new Request(), 'target-uuid');

        $this->assertNotNull($result);
        $this->assertSame('Target', $result->title);
    }

    public function test_find_filtered_returns_null_when_not_found(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A']);

        $result = Post::findFiltered(new Request(), 'nonexistent');

        $this->assertNull($result);
    }

    public function test_find_filtered_applies_visibles_and_hiddens(): void
    {
        $post = Post::create(['uuid' => 'target', 'title' => 'Target', 'status' => 'secret']);

        $request = Request::create('/', 'GET', ['hiddens' => ['status']]);
        $result  = Post::findFiltered($request, 'target');

        $this->assertNotNull($result);
        $this->assertArrayNotHasKey('status', $result->toArray());
    }
}
