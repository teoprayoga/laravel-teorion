<?php

namespace Teoprayoga\Teorion\Tests\Unit\Pipeline;

use Illuminate\Http\Request;
use Teoprayoga\Teorion\Pipeline\ScopedRequest;
use Teoprayoga\Teorion\Tests\TestCase;

class ScopedRequestTest extends TestCase
{
    public function test_isolates_params_from_original_request(): void
    {
        $original = Request::create('/', 'GET', [
            'role_id'        => 99,
            'institution_id' => 1,
            'other_global'   => 'value',
        ]);

        $scoped = ScopedRequest::from($original, ['role_id' => 3]);

        $this->assertSame(3, (int) $scoped->role_id);
        $this->assertNull($scoped->institution_id);
        $this->assertNull($scoped->other_global);
    }

    public function test_preserves_user_resolver(): void
    {
        $original = Request::create('/', 'GET');
        $original->setUserResolver(fn() => (object) ['id' => 42, 'name' => 'Teo']);

        $scoped = ScopedRequest::from($original, ['x' => 1]);

        $this->assertSame(42, $scoped->user()->id);
        $this->assertSame('Teo', $scoped->user()->name);
    }

    public function test_empty_params_means_empty_isolated_request(): void
    {
        $original = Request::create('/', 'GET', ['existing' => 'data']);
        $scoped   = ScopedRequest::from($original, []);

        $this->assertNull($scoped->existing);
    }
}
