<?php

namespace Teoprayoga\Teorion\Exceptions;

use RuntimeException;

class ScopeMethodNotFoundException extends RuntimeException
{
    public function __construct(string $scope, string $modelClass)
    {
        parent::__construct(
            "Scope [{$scope}] is whitelisted but method [scope" . ucfirst($scope) . "] not found on [{$modelClass}]."
        );
    }
}
