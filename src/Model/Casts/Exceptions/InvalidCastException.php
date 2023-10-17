<?php

namespace Mongolid\Model\Casts\Exceptions;

use InvalidArgumentException;
use Mongolid\Model\Casts\CastResolver;

class InvalidCastException extends InvalidArgumentException
{
    public function __construct(string $cast)
    {
        $available = implode(',', CastResolver::$validCasts);
        $message = "Invalid cast attribute: $cast. Use a valid one like $available";

        parent::__construct($message);
    }
}
