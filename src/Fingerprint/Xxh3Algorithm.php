<?php

namespace Teoprayoga\Teorion\Fingerprint;

final class Xxh3Algorithm implements AlgorithmInterface
{
    public function name(): string
    {
        return 'xxh3';
    }

    public function hash(string $payload): string
    {
        return hash('xxh3', $payload);
    }
}
