<?php

namespace Teoprayoga\Teorion\Exceptions;

use RuntimeException;

class QueryFilterNotFoundException extends RuntimeException
{
    public function __construct(string $modelClass, string $expectedClass)
    {
        parent::__construct(
            "QueryFilter could not be resolved for model [{$modelClass}]. "
            . "Expected class [{$expectedClass}] does not exist. "
            . "Either create the class, declare \$queryFilter property, or override newQueryFilter()."
        );
    }
}
