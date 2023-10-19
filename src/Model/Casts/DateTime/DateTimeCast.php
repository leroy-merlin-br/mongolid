<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Util\LocalDateTime;

class DateTimeCast extends BaseDateTimeCast
{
    /**
     * @param UTCDateTime|null $value
     * @return DateTime|null
     */
    public function get(mixed $value): DateTime|null
    {
        if (is_null($value)) {
            return null;
        }

        return LocalDateTime::get($value);
    }
}
