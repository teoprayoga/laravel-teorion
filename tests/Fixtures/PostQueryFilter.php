<?php

namespace Teoprayoga\Teorion\Tests\Fixtures;

use Teoprayoga\Teorion\Filters\BooleanFilter;
use Teoprayoga\Teorion\Filters\ExactFilter;
use Teoprayoga\Teorion\Filters\LikeFilter;
use Teoprayoga\Teorion\Filters\MultiLikeFilter;
use Teoprayoga\Teorion\QueryFilter;

class PostQueryFilter extends QueryFilter
{
    protected array $defaultSort = ['-created_at'];

    public function filters(): array
    {
        return [
            'search'     => new MultiLikeFilter(['title', 'description']),
            'title'      => new LikeFilter('title'),
            'status'     => new ExactFilter('status'),
            'is_active'  => new BooleanFilter('is_active'),
            'is_private' => new BooleanFilter('is_private'),
        ];
    }

    public function allowedScopes(): array
    {
        return ['popular', 'published'];
    }

    public function allowedWiths(): array
    {
        return ['comments'];
    }

    public function allowedWithCounts(): array
    {
        return ['comments'];
    }

    public function allowedSorts(): array
    {
        return ['created_at', 'title', 'view_count'];
    }

    public function allowedAggregates(): array
    {
        return [
            'comments' => [
                'count' => true,
                'sum'   => ['score'],
                'avg'   => ['score'],
                'max'   => ['score'],
                'min'   => ['score'],
            ],
        ];
    }
}
