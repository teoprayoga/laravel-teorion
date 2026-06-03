<?php

namespace Teoprayoga\Teorion\Tests\Feature;

use Teoprayoga\Teorion\Exceptions\QueryFilterNotFoundException;
use Teoprayoga\Teorion\Tests\Fixtures\Article;
use Teoprayoga\Teorion\Tests\Fixtures\ArticleQueryFilter;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class ConventionResolutionTest extends TestCase
{
    public function test_method_override_wins_over_convention(): void
    {
        config(['teorion.query_filters_namespace' => 'NonExistent\\Namespace']);

        $this->assertInstanceOf(PostQueryFilter::class, (new Post())->newQueryFilter());
    }

    public function test_convention_resolves_from_namespace_config(): void
    {
        config(['teorion.query_filters_namespace' => 'Teoprayoga\\Teorion\\Tests\\Fixtures']);

        $this->assertInstanceOf(ArticleQueryFilter::class, (new Article())->newQueryFilter());
    }

    public function test_property_override_beats_convention(): void
    {
        config(['teorion.query_filters_namespace' => 'Teoprayoga\\Teorion\\Tests\\Fixtures']);

        $model = new class extends Article {
            protected string $queryFilter = PostQueryFilter::class;
        };

        $this->assertInstanceOf(PostQueryFilter::class, $model->newQueryFilter());
    }

    public function test_throws_when_neither_resolves(): void
    {
        config(['teorion.query_filters_namespace' => 'NonExistent\\Namespace']);

        $this->expectException(QueryFilterNotFoundException::class);

        (new Article())->newQueryFilter();
    }

    public function test_namespace_with_trailing_backslash_is_normalized(): void
    {
        config(['teorion.query_filters_namespace' => 'Teoprayoga\\Teorion\\Tests\\Fixtures\\']);

        $this->assertInstanceOf(ArticleQueryFilter::class, (new Article())->newQueryFilter());
    }
}
