<?php

namespace Mongolid\Model\Casts;

use BackedEnum;
use Mongolid\Model\Casts\Exceptions\InvalidTypeException;

class BackedEnumCast implements CastInterface
{
    /**
     * @param class-string<BackedEnum> $backedEnum
     */
    public function __construct(
        private string $backedEnum
    ) {
    }


    /**
     * @param int|string|null $value
     */
    public function get(mixed $value): ?BackedEnum
    {
        if (is_null($value)) {
            return null;
        }

        return ($this->backedEnum)::from($value);
    }

    /**
     * @param BackedEnum|string|int|null $value
     */
    public function set(mixed $value): string|int|null
    {
        if (is_null($value)) {
            return null;
        }

        if (!$value instanceof $this->backedEnum) {
            throw new InvalidTypeException(
                "$this->backedEnum|null",
                $value
            );
        }

        return $value->value;
    }
}
