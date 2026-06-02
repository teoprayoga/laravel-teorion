<?php

namespace Teoprayoga\Teorion\Exceptions;

use RuntimeException;

class DisallowedSortException extends RuntimeException
{
    public function __construct(string $column)
    {
        parent::__construct("Sort column [{$column}] is not in the allowed sorts list.");
    }
}
