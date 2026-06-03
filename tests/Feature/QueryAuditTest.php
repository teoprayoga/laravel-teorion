<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Mockery;
use Teoprayoga\Teorion\Events\QueryAudited;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class QueryAuditTest extends TestCase
{
    public function test_audit_is_disabled_by_default(): void
    {
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A']);

        Post::query()->filterAndPaginate(Request::create('/', 'GET', ['is_paginate' => '1']));

        Event::assertNotDispatched(QueryAudited::class);
    }

    public function test_audit_event_is_dispatched_when_enabled(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        Post::query()->filterAndPaginate(Request::create('/', 'GET', ['is_paginate' => '1', 'per_page' => '1']));

        Event::assertDispatched(QueryAudited::class, function (QueryAudited $event): bool {
            return $event->record['filter_class'] === PostQueryFilter::class
                && $event->record['model_class'] === Post::class
                && $event->record['terminal_mode'] === 'paginate'
                && $event->record['limit'] === 1
                && $event->record['result_count'] === 1
                && is_string($event->record['fingerprint']['hash'])
                && $event->record['duration_ms'] >= 0;
        });
    }

    public function test_audit_event_dispatched_for_find_filtered(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        $post = Post::create(['uuid' => 'x', 'title' => 'X']);

        Post::findFiltered(Request::create('/', 'GET'), $post->uuid);

        Event::assertDispatched(QueryAudited::class, function (QueryAudited $event): bool {
            return $event->record['terminal_mode'] === 'find'
                && $event->record['limit'] === 1
                && $event->record['result_count'] === 1
                && $event->record['filter_class'] === PostQueryFilter::class
                && is_string($event->record['fingerprint']['hash']);
        });
    }

    public function test_audit_event_dispatched_for_find_filtered_when_not_found(): void
    {
        config()->set('teorion.audit.enabled', true);
        Event::fake();

        Post::findFiltered(Request::create('/', 'GET'), 'non-existent-uuid');

        Event::assertDispatched(QueryAudited::class, function (QueryAudited $event): bool {
            return $event->record['terminal_mode'] === 'find'
                && $event->record['result_count'] === 0;
        });
    }

    public function test_audit_log_writes_structured_payload_when_enabled(): void
    {
        config()->set('teorion.audit.enabled', true);
        config()->set('teorion.audit.log', true);

        Log::shouldReceive('channel')
            ->once()
            ->with(null)
            ->andReturnSelf();

        Log::shouldReceive('info')
            ->once()
            ->with('teorion.query_audited', Mockery::on(function (array $record): bool {
                return $record['terminal_mode'] === 'collection'
                    && $record['limit'] === 1
                    && $record['result_count'] === 1
                    && isset($record['fingerprint']['hash']);
            }));

        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        Post::query()->filterAndPaginate(Request::create('/', 'GET', ['max_results' => '1']));
    }
}
