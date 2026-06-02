<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\LikeFilter;
use Teoprayoga\Teorion\Filters\MultiLikeFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class LikeFilterTest extends TestCase
{
    public function test_like_filter_matches_substring(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'Laravel Tutorial']);
        Post::create(['uuid' => 'b', 'title' => 'Vue Guide']);

        $filter = new LikeFilter('title');
        $result = $filter->apply(Post::query(), 'Laravel', 'title', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('Laravel Tutorial', $result->first()->title);
    }

    public function test_multi_like_filter_searches_multiple_columns(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'Laravel', 'description' => 'PHP framework']);
        Post::create(['uuid' => 'b', 'title' => 'Vue',     'description' => 'JS framework']);
        Post::create(['uuid' => 'c', 'title' => 'React',   'description' => 'UI library']);

        $filter = new MultiLikeFilter(['title', 'description']);
        $result = $filter->apply(Post::query(), 'framework', 'search', new Request())->get();

        $this->assertCount(2, $result);
    }
}
