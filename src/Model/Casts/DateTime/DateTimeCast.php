<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTime;
use Mongolid\Util\LocalDateTime;

class DateTimeCast extends BaseDateTimeCast
{
    public function get(mixed $value): ?DateTime
    {
        if (is_null($value)) {
            return null;
        }

        return LocalDateTime::get($value);
    }
}
