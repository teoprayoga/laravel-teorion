<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Teoprayoga\Teorion\Events\QueryAudited;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class AuditedBuilderTest extends TestCase
{
    public function test_audited_get_dispatches_audit_event(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        $result = Post::query()->filterAudited(Request::create('/', 'GET'))->get();

        $this->assertCount(2, $result);
        Event::assertDispatched(QueryAudited::class, function (QueryAudited $event): bool {
            return $event->record['terminal_mode'] === 'get'
                && $event->record['result_count'] === 2;
        });
    }

    public function test_audited_supports_chained_where_then_get(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A', 'status' => 'published']);
        Post::create(['uuid' => 'b', 'title' => 'B', 'status' => 'draft']);

        $result = Post::query()->filterAudited(Request::create('/', 'GET'))
            ->where('status', 'published')
            ->get();

        $this->assertCount(1, $result);
        Event::assertDispatched(QueryAudited::class, fn (QueryAudited $e) => $e->record['result_count'] === 1);
    }

    public function test_audited_first_terminal(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A']);

        Post::query()->filterAudited(Request::create('/', 'GET'))->first();

        Event::assertDispatched(QueryAudited::class, function (QueryAudited $event): bool {
            return $event->record['terminal_mode'] === 'first'
                && $event->record['result_count'] === 1;
        });
    }

    public function test_audited_count_terminal(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        $count = Post::query()->filterAudited(Request::create('/', 'GET'))->count();

        $this->assertSame(2, $count);
        Event::assertDispatched(QueryAudited::class, function (QueryAudited $event): bool {
            return $event->record['terminal_mode'] === 'count'
                && $event->record['result_count'] === 2;
        });
    }

    public function test_audited_respects_audit_disabled(): void
    {
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::query()->filterAudited(Request::create('/', 'GET'))->get();

        Event::assertNotDispatched(QueryAudited::class);
    }
}
