<?php

namespace Teoprayoga\Teorion\Tests\Fixtures;

use Teoprayoga\Teorion\Attributes\UsesQueryFilter;

class SampleController
{
    #[UsesQueryFilter(PostQueryFilter::class)]
    public function index()
    {
        // controller body irrelevant for doc extraction tests
    }

    public function show()
    {
        // no attribute
    }
}
