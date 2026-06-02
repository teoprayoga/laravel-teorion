<?php

namespace Teoprayoga\Teorion\Tests\Unit\Terminal;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Teoprayoga\Teorion\Terminal\PaginationTerminal;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class PaginationTerminalTest extends TestCase
{
    public function test_returns_paginator_when_is_paginate_true(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Post::create(['uuid' => "u-$i", 'title' => "P$i"]);
        }

        $terminal = new PaginationTerminal();
        $request  = Request::create('/', 'GET', ['is_paginate' => '1', 'per_page' => '2']);

        $result = $terminal->execute(Post::query(), $request);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertSame(2, $result->perPage());
        $this->assertSame(5, $result->total());
    }

    public function test_returns_collection_when_is_paginate_false(): void
    {
        Post::create(['uuid' => 'a', 'title' => 'A']);
        Post::create(['uuid' => 'b', 'title' => 'B']);

        $terminal = new PaginationTerminal();
        $request  = Request::create('/', 'GET', []);

        $result = $terminal->execute(Post::query(), $request);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }
}
