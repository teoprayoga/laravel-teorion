<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Filters\Filter;
use Teoprayoga\Teorion\Macros\FilterMacroRegistry;
use Teoprayoga\Teorion\Pipeline\FilterPipeline;
use Teoprayoga\Teorion\QueryFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class MacroTest extends TestCase
{
    protected function tearDown(): void
    {
        FilterMacroRegistry::clear();
        parent::tearDown();
    }

    public function test_register_and_resolve_macro(): void
    {
        FilterMacroRegistry::register('upperTitle', function (Builder $q, mixed $value) {
            return $q->whereRaw('UPPER(title) = ?', [strtoupper((string) $value)]);
        });

        $this->assertTrue(FilterMacroRegistry::has('upperTitle'));

        Post::create(['uuid' => 'a', 'title' => 'Laravel']);
        Post::create(['uuid' => 'b', 'title' => 'Vue']);

        $filter = new class extends QueryFilter {
            public function filters(): array
            {
                return [
                    'title_upper' => Filter::macro('upperTitle'),
                ];
            }
        };

        $request = Request::create('/', 'GET', ['title_upper' => 'laravel']);
        $result  = (new FilterPipeline($filter, $request))->run(Post::query())->get();

        $this->assertCount(1, $result);
        $this->assertSame('Laravel', $result->first()->title);
    }

    public function test_unregistered_macro_throws(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Filter macro [missing] is not registered');

        Filter::macro('missing');
    }

    public function test_filter_factory_methods_work(): void
    {
        $this->assertInstanceOf(\Teoprayoga\Teorion\Filters\ExactFilter::class, Filter::exact());
        $this->assertInstanceOf(\Teoprayoga\Teorion\Filters\LikeFilter::class, Filter::like());
        $this->assertInstanceOf(\Teoprayoga\Teorion\Filters\BetweenFilter::class, Filter::between());
        $this->assertInstanceOf(\Teoprayoga\Teorion\Filters\HasFilter::class, Filter::has('comments'));
    }
}
