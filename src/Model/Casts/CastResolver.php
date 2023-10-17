<?php

namespace Mongolid\Model\Casts;

use Mongolid\Model\Casts\DateTime\DateTimeCast;
use Mongolid\Model\Casts\DateTime\ImmutableDateTimeCast;
use Mongolid\Model\Casts\Exceptions\InvalidCastException;

class CastResolver
{
    private const DATE_TIME = 'datetime';
    private const IMMUTABLE_DATE_TIME = 'immutable_datetime';

    public static array $validCasts = [
        self::DATE_TIME,
        self::IMMUTABLE_DATE_TIME,
    ];

    public static function resolve(?string $cast): ?object
    {
        if (is_null($cast)) {
            return null;
        }

        return match($cast) {
            self::DATE_TIME => new DateTimeCast(),
            self::IMMUTABLE_DATE_TIME => new ImmutableDateTimeCast(),
            default => throw new InvalidCastException($cast),
        };
    }
}
