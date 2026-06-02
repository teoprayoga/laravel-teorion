<?php

namespace Teoprayoga\Teorion\Exceptions;

use RuntimeException;

class RequiredFilterMissingException extends RuntimeException
{
    public function __construct(string $param)
    {
        parent::__construct("Required filter parameter [{$param}] is missing from the request.");
    }
}
