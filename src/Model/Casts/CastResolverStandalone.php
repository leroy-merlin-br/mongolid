<?php

namespace Mongolid\Model\Casts;

use BackedEnum;
use Mongolid\Model\Casts\DateTime\DateTimeCast;
use Mongolid\Model\Casts\DateTime\ImmutableDateTimeCast;
use Mongolid\Model\Casts\Exceptions\InvalidCastException;

class CastResolverStandalone implements CastResolverInterface
{
    private const DATE_TIME = 'datetime';
    private const IMMUTABLE_DATE_TIME = 'immutable_datetime';

    public function resolve(string $castIdentifier): CastInterface
    {
        $parts = explode(':', $castIdentifier);
        $castName = $parts[0];
        $castOptions = array_slice($parts, 1);

        return match (true) {
            self::DATE_TIME === $castName => new DateTimeCast(),
            self::IMMUTABLE_DATE_TIME === $castName => new ImmutableDateTimeCast(),
            is_subclass_of($castName, BackedEnum::class) => new BackedEnumCast($castName),
            is_subclass_of($castName, CastInterface::class) => new $castName(...$castOptions),
            default => throw new InvalidCastException($castIdentifier)
        };
    }
}
