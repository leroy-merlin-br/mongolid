<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTimeInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\UTCDateTimeInterface;
use Mongolid\Model\Casts\CastInterface;

abstract class BaseDateTimeCast implements CastInterface
{
    /**
     * @param UTCDateTime $value
     */
    abstract public function get(mixed $value): ?DateTimeInterface;

    /**
     * @param DateTimeInterface|UTCDateTimeInterface $value
     */
    public function set(mixed $value): UTCDateTime|null
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof UTCDateTimeInterface) {
            return $value;
        }

        return new UTCDateTime($value);
    }
}
