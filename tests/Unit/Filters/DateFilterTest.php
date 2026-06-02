<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\DateFilter;
use Teoprayoga\Teorion\Filters\DateRangeFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class DateFilterTest extends TestCase
{
    public function test_date_filter_matches_specific_date(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'event_date' => '2026-01-15']);
        Post::create(['uuid' => 'b', 'title' => 'B', 'event_date' => '2026-02-20']);

        $filter = new DateFilter('event_date');
        $result = $filter->apply(Post::query(), '2026-01-15', 'event_date', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_date_range_filter_with_both_bounds(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'event_date' => '2026-01-15']);
        Post::create(['uuid' => 'b', 'title' => 'B', 'event_date' => '2026-02-20']);
        Post::create(['uuid' => 'c', 'title' => 'C', 'event_date' => '2026-03-10']);

        $filter  = new DateRangeFilter('event_date', fromParam: 'from', toParam: 'to');
        $request = Request::create('/', 'GET', ['from' => '2026-01-01', 'to' => '2026-02-28']);

        $result = $filter->apply(Post::query(), '_unused', 'event_date', $request)->get();

        $this->assertCount(2, $result);
    }
}
