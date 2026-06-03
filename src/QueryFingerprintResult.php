<?php

namespace Teoprayoga\Teorion;

final class QueryFingerprintResult
{
    public function __construct(
        public readonly string $hash,
        public readonly array $payload,
        public readonly string $algorithm = 'sha256',
    ) {
    }
}
