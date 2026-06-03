<?php

namespace Teoprayoga\Teorion\Tests\Unit;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\QueryFingerprint;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class QueryFingerprintTest extends TestCase
{
    public function test_parameter_order_does_not_change_hash(): void
    {
        $service = new QueryFingerprint();
        $filter  = new PostQueryFilter();

        $first = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'status' => 'published',
            'sort'   => '-created_at',
            'withs'  => ['comments'],
        ]));

        $second = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'withs'  => ['comments'],
            'sort'   => '-created_at',
            'status' => 'published',
        ]));

        $this->assertSame($first->hash, $second->hash);
        $this->assertSame('sha256', $first->algorithm);
    }

    public function test_page_and_cursor_do_not_change_hash(): void
    {
        $service = new QueryFingerprint();
        $filter  = new PostQueryFilter();

        $first = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'status' => 'published',
            'page'   => 1,
            'cursor' => 'abc',
        ]));

        $second = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'status' => 'published',
            'page'   => 2,
            'cursor' => 'def',
        ]));

        $this->assertSame($first->hash, $second->hash);
        $this->assertArrayNotHasKey('page', $first->payload['parameters']);
        $this->assertArrayNotHasKey('cursor', $first->payload['parameters']);
    }

    public function test_query_intent_changes_change_hash(): void
    {
        $service = new QueryFingerprint();
        $filter  = new PostQueryFilter();

        $first = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'status' => 'published',
            'sort'   => '-created_at',
        ]));

        $second = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'status' => 'draft',
            'sort'   => '-created_at',
        ]));

        $third = $service->make($filter, Post::query(), Request::create('/', 'GET', [
            'status' => 'published',
            'sort'   => 'title',
        ]));

        $this->assertNotSame($first->hash, $second->hash);
        $this->assertNotSame($first->hash, $third->hash);
    }
}
