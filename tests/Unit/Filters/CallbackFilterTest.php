<?php

namespace Teoprayoga\Teorion\Tests\Unit\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\CallbackFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class CallbackFilterTest extends TestCase
{
    public function test_closure_receives_builder_and_value(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 200]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 50]);

        $filter = new CallbackFilter(
            fn(Builder $q, mixed $value) => $q->where('view_count', '>=', (int) $value)
        );

        $result = $filter->apply(Post::query(), '100', 'min_views', new Request())->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_closure_can_access_request(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'is_active' => true]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'is_active' => false]);

        $filter = new CallbackFilter(
            function (Builder $q, mixed $value, string $param, Request $request) {
                $invert = $request->boolean('invert');
                return $invert
                    ? $q->where('is_active', false)
                    : $q->where('is_active', true);
            }
        );

        $request = Request::create('/', 'GET', ['invert' => '1']);
        $result  = $filter->apply(Post::query(), 'any', 'has_pic', $request)->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }
}
