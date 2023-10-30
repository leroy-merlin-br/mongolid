<?php

namespace Mongolid\Model\Casts\Exceptions;

use InvalidArgumentException;
use Mongolid\Model\Casts\CastResolver;

class InvalidTypeException extends InvalidArgumentException
{
    public function __construct(string $expectedType, mixed $value)
    {
        $invalidType = is_object($value)
            ? $value::class
            : gettype($value);

        parent::__construct("Value expected type $expectedType, given $invalidType");
    }
}
