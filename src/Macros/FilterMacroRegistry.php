<?php

namespace Teoprayoga\Teorion\Macros;

use Closure;
use RuntimeException;

/**
 * Global registry for filter macros.
 *
 * Usage (in AppServiceProvider::boot()):
 *   FilterMacroRegistry::register('phone', function ($q, $value, $param) {
 *       return $q->where($param, preg_replace('/\D/', '', $value));
 *   });
 *
 * Then in QueryFilter::filters():
 *   'phone_number' => Filter::macro('phone')
 */
class FilterMacroRegistry
{
    private static array $macros = [];

    public static function register(string $name, Closure $callback): void
    {
        self::$macros[$name] = $callback;
    }

    public static function has(string $name): bool
    {
        return isset(self::$macros[$name]);
    }

    public static function get(string $name): Closure
    {
        if (!self::has($name)) {
            throw new RuntimeException("Filter macro [{$name}] is not registered.");
        }

        return self::$macros[$name];
    }

    public static function clear(): void
    {
        self::$macros = [];
    }

    public static function all(): array
    {
        return self::$macros;
    }
}
