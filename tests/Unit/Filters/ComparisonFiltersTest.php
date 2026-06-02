<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\BetweenFilter;
use Teoprayoga\Teorion\Filters\GreaterThanFilter;
use Teoprayoga\Teorion\Filters\HasFilter;
use Teoprayoga\Teorion\Filters\LessThanFilter;
use Teoprayoga\Teorion\Filters\RangeFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class ComparisonFiltersTest extends TestCase
{
    public function test_between_filter_with_array(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 50]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 150]);
        Post::create(['uuid' => 'c', 'title' => 'C', 'view_count' => 250]);

        $filter = new BetweenFilter('view_count');
        $result = $filter->apply(Post::query(), [100, 200], 'view_count', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }

    public function test_between_filter_with_comma_string(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 50]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 150]);

        $filter = new BetweenFilter('view_count');
        $result = $filter->apply(Post::query(), '100,200', 'view_count', new Request())->get();

        $this->assertCount(1, $result);
    }

    public function test_range_filter_reads_min_and_max_from_request(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 50]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 150]);
        Post::create(['uuid' => 'c', 'title' => 'C', 'view_count' => 250]);

        $filter  = new RangeFilter('view_count');
        $request = Request::create('/', 'GET', [
            'view_count_min' => 100,
            'view_count_max' => 200,
        ]);

        $result = $filter->apply(Post::query(), '_trigger', 'view_count', $request)->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }

    public function test_greater_than_filter(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 50]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 100]);
        Post::create(['uuid' => 'c', 'title' => 'C', 'view_count' => 150]);

        $filter = new GreaterThanFilter('view_count');
        $this->assertCount(1, $filter->apply(Post::query(), 100, 'view_count', new Request())->get());

        $filterGte = new GreaterThanFilter('view_count', orEqual: true);
        $this->assertCount(2, $filterGte->apply(Post::query(), 100, 'view_count', new Request())->get());
    }

    public function test_less_than_filter(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 50]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 100]);
        Post::create(['uuid' => 'c', 'title' => 'C', 'view_count' => 150]);

        $filter = new LessThanFilter('view_count');
        $this->assertCount(1, $filter->apply(Post::query(), 100, 'view_count', new Request())->get());

        $filterLte = new LessThanFilter('view_count', orEqual: true);
        $this->assertCount(2, $filterLte->apply(Post::query(), 100, 'view_count', new Request())->get());
    }

    public function test_has_filter_with_truthy_value_returns_models_with_relation(): void
    {
        $a = Post::create(['uuid' => 'a', 'title' => 'A']);
        $a->comments()->create(['body' => 'first']);
        Post::create(['uuid' => 'b', 'title' => 'B']);  // no comments

        $filter = new HasFilter('comments');
        $result = $filter->apply(Post::query(), '1', 'has_comments', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_has_filter_with_falsy_value_returns_models_without_relation(): void
    {
        $a = Post::create(['uuid' => 'a', 'title' => 'A']);
        $a->comments()->create(['body' => 'first']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        $filter = new HasFilter('comments');
        $result = $filter->apply(Post::query(), '0', 'has_comments', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }
}
