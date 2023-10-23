<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Util\LocalDateTime;

class DateTimeCast extends BaseDateTimeCast
{
    /**
     * @param UTCDateTime|null $value
     */
    public function get(mixed $value): ?DateTime
    {
        if (is_null($value)) {
            return null;
        }

        return LocalDateTime::get($value);
    }
}
