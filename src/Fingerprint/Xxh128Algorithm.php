<?php

namespace Teoprayoga\Teorion\Fingerprint;

final class Xxh128Algorithm implements AlgorithmInterface
{
    public function name(): string
    {
        return 'xxh128';
    }

    public function hash(string $payload): string
    {
        return hash('xxh128', $payload);
    }
}
