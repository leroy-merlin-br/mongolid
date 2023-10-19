<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTimeImmutable;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Util\LocalDateTime;

class ImmutableDateTimeCast extends BaseDateTimeCast
{
    /**
     * @param UTCDateTime|null $value
     * @return DateTimeImmutable|null
     */
    public function get(mixed $value): DateTimeImmutable|null
    {
        if (is_null($value)) {
            return null;
        }

        return DateTimeImmutable::createFromMutable(
            LocalDateTime::get($value)
        );
    }
}
