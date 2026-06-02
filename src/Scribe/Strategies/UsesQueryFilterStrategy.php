<?php

namespace Teoprayoga\Teorion\Scribe\Strategies;

use ReflectionClass;
use ReflectionMethod;
use Teoprayoga\Teorion\Attributes\UsesQueryFilter;
use Teoprayoga\Teorion\Scribe\QueryFilterDocsExtractor;

/**
 * Scribe Strategy plugin: extracts query parameters from a controller method's
 * #[UsesQueryFilter] attribute and translates them to Scribe's query param schema.
 *
 * Register in config/scribe.php:
 *   'strategies' => [
 *       'queryParameters' => [
 *           ...,
 *           \Teoprayoga\Teorion\Scribe\Strategies\UsesQueryFilterStrategy::class,
 *       ],
 *   ],
 *
 * The class is intentionally NOT extending Scribe's base Strategy class to avoid
 * a hard dependency on knuckleswtf/scribe. Scribe will accept any class with an
 * __invoke() method that returns the params array.
 */
class UsesQueryFilterStrategy
{
    public function __construct(protected mixed $config = null) {}

    /**
     * Scribe calls this with an ExtractedEndpointData object.
     * We extract the controller class + method via reflection-friendly properties.
     */
    public function __invoke($endpointData, array $routeRules = []): array
    {
        $reflectionMethod = $this->reflectMethod($endpointData);

        if ($reflectionMethod === null) {
            return [];
        }

        $attributes = $reflectionMethod->getAttributes(UsesQueryFilter::class);

        if (empty($attributes)) {
            return [];
        }

        /** @var UsesQueryFilter $usesFilter */
        $usesFilter = $attributes[0]->newInstance();

        return (new QueryFilterDocsExtractor())->extract($usesFilter->filterClass);
    }

    /**
     * Best-effort reflection — supports both Scribe's ExtractedEndpointData object
     * and a plain array with 'method' + 'controller' keys (for tests).
     */
    private function reflectMethod(mixed $endpointData): ?ReflectionMethod
    {
        if (is_object($endpointData) && isset($endpointData->method) && $endpointData->method instanceof ReflectionMethod) {
            return $endpointData->method;
        }

        if (is_array($endpointData) && isset($endpointData['controller'], $endpointData['method'])) {
            try {
                return new ReflectionMethod($endpointData['controller'], $endpointData['method']);
            } catch (\ReflectionException) {
                return null;
            }
        }

        return null;
    }
}
