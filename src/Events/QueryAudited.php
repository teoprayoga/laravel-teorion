<?php

namespace Teoprayoga\Teorion\Events;

class QueryAudited
{
    public function __construct(public readonly array $record)
    {
    }
}
