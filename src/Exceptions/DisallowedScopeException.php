<?php

namespace Teoprayoga\Teorion\Exceptions;

use RuntimeException;

class DisallowedScopeException extends RuntimeException
{
    public function __construct(string $scope)
    {
        parent::__construct("Scope [{$scope}] is not in the allowed scopes list.");
    }
}
