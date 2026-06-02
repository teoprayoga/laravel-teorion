<?php

namespace Teoprayoga\Teorion\Tests\Unit;

use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\TestCase;

class SmokeTest extends TestCase
{
    public function test_post_table_exists_and_model_can_be_created(): void
    {
        $post = Post::create([
            'uuid'  => 'test-uuid',
            'title' => 'Hello World',
        ]);

        $this->assertSame('Hello World', $post->title);
        $this->assertSame(1, Post::count());
    }

    public function test_filterable_trait_is_loaded(): void
    {
        $this->assertContains(
            \Teoprayoga\Teorion\Traits\Filterable::class,
            class_uses_recursive(Post::class)
        );
    }
}
