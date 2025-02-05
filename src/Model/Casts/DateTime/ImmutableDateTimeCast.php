<?php

namespace Mongolid\Model\Casts\DateTime;

use DateTimeImmutable;
use Mongolid\Util\LocalDateTime;

class ImmutableDateTimeCast extends BaseDateTimeCast
{
    public function get(mixed $value): ?DateTimeImmutable
    {
        if (is_null($value)) {
            return null;
        }

        return DateTimeImmutable::createFromMutable(
            LocalDateTime::get($value)
        );
    }
}
