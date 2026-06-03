<?php

namespace Teoprayoga\Teorion\Fingerprint;

interface AlgorithmInterface
{
    public function name(): string;

    public function hash(string $payload): string;
}
