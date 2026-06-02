<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Pipeline\FilterPipeline;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class AggregateTest extends TestCase
{
    public function test_count_aggregate_loads_count(): void
    {
        $post = Post::create(['uuid' => 'a', 'title' => 'A']);
        $post->comments()->create(['body' => 'one',   'score' => 5]);
        $post->comments()->create(['body' => 'two',   'score' => 10]);
        $post->comments()->create(['body' => 'three', 'score' => 15]);

        $request = Request::create('/', 'GET', [
            'withAggregates' => ['comments' => ['count' => 1]],
        ]);

        $result = (new FilterPipeline(new PostQueryFilter(), $request))
            ->run(Post::query())
            ->first();

        $this->assertSame(3, (int) $result->comments_count);
    }

    public function test_sum_aggregate_loads_sum(): void
    {
        $post = Post::create(['uuid' => 'a', 'title' => 'A']);
        $post->comments()->create(['body' => 'one', 'score' => 5]);
        $post->comments()->create(['body' => 'two', 'score' => 10]);

        $request = Request::create('/', 'GET', [
            'withAggregates' => ['comments' => ['sum' => ['score']]],
        ]);

        $result = (new FilterPipeline(new PostQueryFilter(), $request))
            ->run(Post::query())
            ->first();

        $this->assertSame(15, (int) $result->comments_sum_score);
    }

    public function test_avg_max_min_aggregates(): void
    {
        $post = Post::create(['uuid' => 'a', 'title' => 'A']);
        $post->comments()->create(['body' => 'one', 'score' => 10]);
        $post->comments()->create(['body' => 'two', 'score' => 20]);

        $request = Request::create('/', 'GET', [
            'withAggregates' => [
                'comments' => [
                    'avg' => ['score'],
                    'max' => ['score'],
                    'min' => ['score'],
                ],
            ],
        ]);

        $result = (new FilterPipeline(new PostQueryFilter(), $request))
            ->run(Post::query())
            ->first();

        $this->assertSame(15.0, (float) $result->comments_avg_score);
        $this->assertSame(20,   (int) $result->comments_max_score);
        $this->assertSame(10,   (int) $result->comments_min_score);
    }

    public function test_unlisted_aggregate_relation_is_silently_skipped(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A']);

        $request = Request::create('/', 'GET', [
            'withAggregates' => ['unknown_relation' => ['sum' => ['score']]],
        ]);

        // Should not throw
        $result = (new FilterPipeline(new PostQueryFilter(), $request))
            ->run(Post::query())
            ->first();

        $this->assertNotNull($result);
    }

    public function test_unlisted_aggregate_column_is_silently_skipped(): void
    {
        $post = Post::create(['uuid' => 'a', 'title' => 'A']);
        $post->comments()->create(['body' => 'one', 'score' => 5]);

        // 'body' is not in allowedAggregates for sum
        $request = Request::create('/', 'GET', [
            'withAggregates' => ['comments' => ['sum' => ['body']]],
        ]);

        $result = (new FilterPipeline(new PostQueryFilter(), $request))
            ->run(Post::query())
            ->first();

        $this->assertObjectNotHasProperty('comments_sum_body', $result);
    }
}
