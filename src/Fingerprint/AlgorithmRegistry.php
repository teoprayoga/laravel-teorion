<?php

namespace Teoprayoga\Teorion\Fingerprint;

use InvalidArgumentException;

class AlgorithmRegistry
{
    /** @var array<string, AlgorithmInterface> */
    private static array $algorithms = [];

    public static function register(AlgorithmInterface $algorithm): void
    {
        self::$algorithms[$algorithm->name()] = $algorithm;
    }

    public static function get(string $name): AlgorithmInterface
    {
        if (!isset(self::$algorithms[$name])) {
            throw new InvalidArgumentException(sprintf(
                'Fingerprint algorithm [%s] is not registered. Available: %s',
                $name,
                implode(', ', array_keys(self::$algorithms)) ?: '(none)'
            ));
        }

        return self::$algorithms[$name];
    }

    public static function has(string $name): bool
    {
        return isset(self::$algorithms[$name]);
    }

    /** @return array<string, AlgorithmInterface> */
    public static function all(): array
    {
        return self::$algorithms;
    }

    public static function clear(): void
    {
        self::$algorithms = [];
    }
}
