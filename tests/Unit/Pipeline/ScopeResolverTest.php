<?php

namespace Teoprayoga\Teorion\Tests\Unit\Pipeline;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\DisallowedScopeException;
use Teoprayoga\Teorion\Pipeline\ScopeResolver;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class ScopeResolverTest extends TestCase
{
    public function test_legacy_string_format_calls_scope_with_full_request(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 200]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 5]);

        $resolver = new ScopeResolver();
        $request  = Request::create('/', 'GET', ['view_threshold' => 100]);

        $result = $resolver->resolve(
            Post::query(),
            ['popular'],          // legacy string format
            ['popular'],          // allowed
            $request,
        )->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_new_format_isolates_scope_params(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'view_count' => 200]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'view_count' => 150]);
        Post::create(['uuid' => 'c', 'title' => 'C', 'view_count' => 50]);

        $resolver = new ScopeResolver();
        $request  = Request::create('/', 'GET', [
            'view_threshold' => 999,   // global request — should NOT leak
        ]);

        $result = $resolver->resolve(
            Post::query(),
            [['name' => 'popular', 'params' => ['view_threshold' => 100]]],
            ['popular'],
            $request,
        )->get();

        // Scope uses scoped params (100), not global (999)
        $this->assertCount(2, $result);
    }

    public function test_throws_when_scope_not_in_whitelist(): void
    {
        $resolver = new ScopeResolver();

        $this->expectException(DisallowedScopeException::class);

        $resolver->resolve(
            Post::query(),
            ['notWhitelisted'],
            ['popular', 'published'],
            new Request(),
        );
    }

    public function test_strict_mode_throws_when_scope_method_missing(): void
    {
        config(['teorion.strict_mode' => true]);

        $resolver = new ScopeResolver();

        $this->expectException(\Teoprayoga\Teorion\Exceptions\ScopeMethodNotFoundException::class);

        $resolver->resolve(
            Post::query(),
            ['phantomScope'],
            ['phantomScope'],
            new Request(),
        );
    }
}
