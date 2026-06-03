<?php

namespace Teoprayoga\Teorion\Tests\Unit;

use Illuminate\Http\Request;
use InvalidArgumentException;
use Teoprayoga\Teorion\Fingerprint\AlgorithmInterface;
use Teoprayoga\Teorion\Fingerprint\AlgorithmRegistry;
use Teoprayoga\Teorion\QueryFingerprint;
use Teoprayoga\Teorion\Tests\Fixtures\Post;
use Teoprayoga\Teorion\Tests\Fixtures\PostQueryFilter;
use Teoprayoga\Teorion\Tests\TestCase;

class FingerprintAlgorithmTest extends TestCase
{
    public function test_default_algorithm_is_sha256(): void
    {
        $result = (new QueryFingerprint())->make(
            new PostQueryFilter(),
            Post::query(),
            Request::create('/', 'GET')
        );

        $this->assertSame('sha256', $result->algorithm);
        $this->assertSame(64, strlen($result->hash));
    }

    public function test_xxh3_algorithm_used_when_configured(): void
    {
        config()->set('teorion.fingerprint.algorithm', 'xxh3');

        $result = (new QueryFingerprint())->make(
            new PostQueryFilter(),
            Post::query(),
            Request::create('/', 'GET')
        );

        $this->assertSame('xxh3', $result->algorithm);
        $this->assertSame(16, strlen($result->hash));
    }

    public function test_xxh128_algorithm_used_when_configured(): void
    {
        config()->set('teorion.fingerprint.algorithm', 'xxh128');

        $result = (new QueryFingerprint())->make(
            new PostQueryFilter(),
            Post::query(),
            Request::create('/', 'GET')
        );

        $this->assertSame('xxh128', $result->algorithm);
        $this->assertSame(32, strlen($result->hash));
    }

    public function test_unknown_algorithm_throws(): void
    {
        config()->set('teorion.fingerprint.algorithm', 'made-up-algorithm');

        $this->expectException(InvalidArgumentException::class);

        (new QueryFingerprint())->make(
            new PostQueryFilter(),
            Post::query(),
            Request::create('/', 'GET')
        );
    }

    public function test_custom_algorithm_can_be_registered(): void
    {
        AlgorithmRegistry::register(new class implements AlgorithmInterface {
            public function name(): string
            {
                return 'test-fake';
            }

            public function hash(string $payload): string
            {
                return str_repeat('a', 16);
            }
        });

        config()->set('teorion.fingerprint.algorithm', 'test-fake');

        $result = (new QueryFingerprint())->make(
            new PostQueryFilter(),
            Post::query(),
            Request::create('/', 'GET')
        );

        $this->assertSame('test-fake', $result->algorithm);
        $this->assertSame(str_repeat('a', 16), $result->hash);
    }
}
