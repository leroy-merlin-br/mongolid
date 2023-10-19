<?php

namespace Mongolid\Model\Casts;

use Mongolid\Model\Casts\DateTime\DateTimeCast;
use Mongolid\Model\Casts\DateTime\ImmutableDateTimeCast;
use Mongolid\Model\Casts\Exceptions\InvalidCastException;

class CastResolver
{
    private const DATE_TIME = 'datetime';
    private const IMMUTABLE_DATE_TIME = 'immutable_datetime';

    private static array $cache = [];

    public static array $validCasts = [
        self::DATE_TIME,
        self::IMMUTABLE_DATE_TIME,
    ];

    public static function resolve(string $castName): CastInterface
    {
        if ($cast = self::$cache[$castName] ?? null) {
            return $cast;
        }

        self::$cache[$castName] = match($castName) {
            self::DATE_TIME => new DateTimeCast(),
            self::IMMUTABLE_DATE_TIME => new ImmutableDateTimeCast(),
            default => throw new InvalidCastException($castName),
        };

        return self::$cache[$castName];
    }
}
