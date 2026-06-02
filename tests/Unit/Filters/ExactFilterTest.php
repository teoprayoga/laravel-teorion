<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\ExactFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class ExactFilterTest extends TestCase
{
    public function test_filters_by_exact_column_value(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'status' => 'published']);
        Post::create(['uuid' => 'b', 'title' => 'B', 'status' => 'draft']);

        $filter = new ExactFilter('status');
        $query  = Post::query();
        $result = $filter->apply($query, 'published', 'status', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_falls_back_to_param_name_when_column_not_specified(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'status' => 'draft']);

        $filter = new ExactFilter();
        $query  = Post::query();
        $result = $filter->apply($query, 'draft', 'status', new Request())->get();

        $this->assertCount(1, $result);
    }
}
