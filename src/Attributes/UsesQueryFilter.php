<?php

namespace Teoprayoga\Teorion\Attributes;

use Attribute;

/**
 * Marks a controller method as using a QueryFilter for auto-generated API docs.
 *
 * Usage:
 *   #[UsesQueryFilter(ContentQueryFilter::class)]
 *   public function index(GetRequest $request): JsonResponse { ... }
 */
#[Attribute(Attribute::TARGET_METHOD)]
class UsesQueryFilter
{
    public function __construct(public readonly string $filterClass) {}
}
