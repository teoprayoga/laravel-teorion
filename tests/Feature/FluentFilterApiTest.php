<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Exceptions\RequiredFilterMissingException;
use Teoprayoga\Teorion\Filters\BooleanFilter;
use Teoprayoga\Teorion\Filters\ExactFilter;
use Teoprayoga\Teorion\Filters\MultiLikeFilter;
use Teoprayoga\Teorion\Pipeline\FilterPipeline;
use Teoprayoga\Teorion\QueryFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class FluentFilterApiTest extends TestCase
{
    public function test_alias_reads_from_aliased_request_param(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'Laravel Tutorial']);
        Post::create(['uuid' => 'b', 'title' => 'Vue Guide']);

        $filter = new class extends QueryFilter {
            public function filters(): array
            {
                return [
                    'search' => (new MultiLikeFilter(['title', 'description']))->alias('q'),
                ];
            }
        };

        $request = Request::create('/', 'GET', ['q' => 'Laravel']);
        $result  = (new FilterPipeline($filter, $request))->run(Post::query())->get();

        $this->assertCount(1, $result);
        $this->assertSame('Laravel Tutorial', $result->first()->title);
    }

    public function test_default_value_applied_when_param_absent(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'is_active' => true]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'is_active' => false]);

        $filter = new class extends QueryFilter {
            public function filters(): array
            {
                return [
                    'is_active' => (new BooleanFilter())->default(true),
                ];
            }
        };

        $request = Request::create('/', 'GET', []);  // no is_active param
        $result  = (new FilterPipeline($filter, $request))->run(Post::query())->get();

        $this->assertCount(1, $result);
        $this->assertSame('A', $result->first()->title);
    }

    public function test_default_overridden_when_param_provided(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'is_active' => true]);
        Post::create(['uuid' => 'b', 'title' => 'B', 'is_active' => false]);

        $filter = new class extends QueryFilter {
            public function filters(): array
            {
                return [
                    'is_active' => (new BooleanFilter())->default(true),
                ];
            }
        };

        $request = Request::create('/', 'GET', ['is_active' => '0']);
        $result  = (new FilterPipeline($filter, $request))->run(Post::query())->get();

        $this->assertCount(1, $result);
        $this->assertSame('B', $result->first()->title);
    }

    public function test_required_filter_throws_when_missing(): void
    {
        $filter = new class extends QueryFilter {
            public function filters(): array
            {
                return [
                    'status' => (new ExactFilter())->required(),
                ];
            }
        };

        $request = Request::create('/', 'GET', []);

        $this->expectException(RequiredFilterMissingException::class);
        (new FilterPipeline($filter, $request))->run(Post::query())->get();
    }

    public function test_required_filter_passes_when_present(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A', 'status' => 'published']);

        $filter = new class extends QueryFilter {
            public function filters(): array
            {
                return [
                    'status' => (new ExactFilter())->required(),
                ];
            }
        };

        $request = Request::create('/', 'GET', ['status' => 'published']);
        $result  = (new FilterPipeline($filter, $request))->run(Post::query())->get();

        $this->assertCount(1, $result);
    }
}
