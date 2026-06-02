<?php

namespace Teoprayoga\Teorion\Exceptions;

use RuntimeException;

class DisallowedWithException extends RuntimeException
{
    public function __construct(string $relation)
    {
        parent::__construct("Relation [{$relation}] is not in the allowed withs list.");
    }
}
