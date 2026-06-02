<?php

namespace Teoprayoga\Teorion\Tests\Unit\Pipeline;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Pipeline\FilterPipeline;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class FilterPipelineTest extends TestCase
{
    public function test_applies_only_filters_with_present_params(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'Laravel', 'status' => 'published']);
        Post::create(['uuid' => 'b', 'title' => 'Vue',     'status' => 'draft']);
        Post::create(['uuid' => 'c', 'title' => 'Laravel', 'status' => 'draft']);

        $request  = Request::create('/', 'GET', ['title' => 'Laravel', 'status' => 'draft']);
        $pipeline = new FilterPipeline(new PostQueryFilter(), $request);
        $result   = $pipeline->run(Post::query())->get();

        $this->assertCount(1, $result);
        $this->assertSame('c', $result->first()->uuid);
    }

    public function test_skips_filters_when_param_absent(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        $request  = Request::create('/', 'GET', []);
        $pipeline = new FilterPipeline(new PostQueryFilter(), $request);
        $result   = $pipeline->run(Post::query())->get();

        $this->assertCount(2, $result);
    }

    public function test_applies_withs_when_in_whitelist(): void
    {
        $post = Post::create(['uuid' => 'a', 'title' => 'A']);
        $post->comments()->create(['body' => 'First']);

        $request  = Request::create('/', 'GET', ['withs' => ['comments']]);
        $pipeline = new FilterPipeline(new PostQueryFilter(), $request);
        $result   = $pipeline->run(Post::query())->get();

        $this->assertTrue($result->first()->relationLoaded('comments'));
        $this->assertCount(1, $result->first()->comments);
    }

    public function test_with_trashed_includes_soft_deleted(): void
    {
        $a = Post::create(['uuid' => 'a', 'title' => 'A']);
        $b = Post::create(['uuid' => 'b', 'title' => 'B']);
        $b->delete();

        $request  = Request::create('/', 'GET', ['with_trashed' => '1']);
        $pipeline = new FilterPipeline(new PostQueryFilter(), $request);
        $result   = $pipeline->run(Post::query())->get();

        $this->assertCount(2, $result);
    }

    public function test_only_trashed_returns_only_soft_deleted(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A']);
        $b = Post::create(['uuid' => 'b', 'title' => 'B']);
        $b->delete();

        $request  = Request::create('/', 'GET', ['only_trashed' => '1']);
        $pipeline = new FilterPipeline(new PostQueryFilter(), $request);
        $result   = $pipeline->run(Post::query())->get();

        $this->assertCount(1, $result);
        $this->assertSame('b', $result->first()->uuid);
    }

    public function test_default_sort_is_applied_when_no_sort_param(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'created_at' => now()->subDays(2)]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'created_at' => now()]);

        $request  = Request::create('/', 'GET', []);
        $pipeline = new FilterPipeline(new PostQueryFilter(), $request);
        $result   = $pipeline->run(Post::query())->get();

        // defaultSort = ['-created_at'] → newest first
        $this->assertSame('b', $result->first()->uuid);
    }
}
