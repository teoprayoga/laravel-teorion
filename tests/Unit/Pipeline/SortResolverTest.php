<?php

namespace Teoprayoga\Teorion\Tests\Unit\Pipeline;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\DisallowedSortException;
use Teoprayoga\Teorion\Pipeline\SortResolver;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class SortResolverTest extends TestCase
{
    public function test_parses_spatie_style_format(): void
    {
        $resolver = new SortResolver();
        $request  = Request::create('/', 'GET', ['sort' => '-created_at,title']);

        $parsed = $resolver->parse($request);

        $this->assertSame(['created_at' => 'desc', 'title' => 'asc'], $parsed);
    }

    public function test_parses_legacy_single_format(): void
    {
        $resolver = new SortResolver();
        $request  = Request::create('/', 'GET', ['order_by' => 'title', 'order_direction' => 'desc']);

        $parsed = $resolver->parse($request);

        $this->assertSame(['title' => 'desc'], $parsed);
    }

    public function test_parses_legacy_array_format(): void
    {
        $resolver = new SortResolver();
        $request  = Request::create('/', 'GET', [
            'order' => [
                ['by' => 'title',      'direction' => 'desc'],
                ['by' => 'created_at', 'direction' => 'asc'],
            ],
        ]);

        $parsed = $resolver->parse($request);

        $this->assertSame(['title' => 'desc', 'created_at' => 'asc'], $parsed);
    }

    public function test_applies_sort_to_query(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'B Title', 'view_count' => 50]);
        Post::create(['uuid' => 'b', 'title' => 'A Title', 'view_count' => 100]);

        $resolver = new SortResolver();
        $request  = Request::create('/', 'GET', ['sort' => 'title']);

        $result = $resolver->resolve(Post::query(), $request, new PostQueryFilter())->get();

        $this->assertSame('A Title', $result[0]->title);
        $this->assertSame('B Title', $result[1]->title);
    }

    public function test_throws_when_sort_not_allowed(): void
    {
        $resolver = new SortResolver();
        $request  = Request::create('/', 'GET', ['sort' => 'evil_column']);

        $this->expectException(DisallowedSortException::class);

        $resolver->resolve(Post::query(), $request, new PostQueryFilter());
    }
}
