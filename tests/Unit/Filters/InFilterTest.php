<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\InFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class InFilterTest extends TestCase
{
    public function test_accepts_array_value(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'status' => 'draft']);
        Post::create(['uuid' => 'b', 'title' => 'B', 'status' => 'published']);
        Post::create(['uuid' => 'c', 'title' => 'C', 'status' => 'archived']);

        $filter = new InFilter('status');
        $result = $filter->apply(Post::query(), ['draft', 'archived'], 'status', new Request())->get();

        $this->assertCount(2, $result);
    }

    public function test_accepts_comma_separated_string(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'status' => 'draft']);
        Post::create(['uuid' => 'b', 'title' => 'B', 'status' => 'published']);

        $filter = new InFilter('status');
        $result = $filter->apply(Post::query(), 'draft,published', 'status', new Request())->get();

        $this->assertCount(2, $result);
    }
}
