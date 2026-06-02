<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\NullFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class NullFilterTest extends TestCase
{
    public function test_truthy_value_returns_not_null(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'published_at' => now()]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'published_at' => null]);

        $filter = new NullFilter('published_at');
        $result = $filter->apply(Post::query(), '1', 'published_at', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_falsy_value_returns_null(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'published_at' => now()]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'published_at' => null]);

        $filter = new NullFilter('published_at');
        $result = $filter->apply(Post::query(), '0', 'published_at', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }
}
