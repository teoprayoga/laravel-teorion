<?php

namespace Teoprayoga\Teorion\Fingerprint;

final class Sha256Algorithm implements AlgorithmInterface
{
    public function name(): string
    {
        return 'sha256';
    }

    public function hash(string $payload): string
    {
        return hash('sha256', $payload);
    }
}
