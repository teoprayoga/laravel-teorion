<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\BooleanFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class BooleanFilterTest extends TestCase
{
    public function test_truthy_value_filters_for_true(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'is_active' => true]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'is_active' => false]);

        $filter = new BooleanFilter('is_active');

        $this->assertCount(1, $filter->apply(Post::query(), '1',    'is_active', new Request())->get());
        $this->assertCount(1, $filter->apply(Post::query(), 'true', 'is_active', new Request())->get());
        $this->assertCount(1, $filter->apply(Post::query(), 1,      'is_active', new Request())->get());
    }

    public function test_falsy_value_filters_for_false(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'is_active' => true]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'is_active' => false]);

        $filter = new BooleanFilter('is_active');
        $result = $filter->apply(Post::query(), '0', 'is_active', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }
}
