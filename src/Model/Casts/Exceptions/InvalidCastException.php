<?php

namespace Mongolid\Model\Casts\Exceptions;

use InvalidArgumentException;

class InvalidCastException extends InvalidArgumentException
{
    public function __construct(string $cast)
    {
        $message = "Invalid cast attribute: $cast";

        parent::__construct($message);
    }
}
