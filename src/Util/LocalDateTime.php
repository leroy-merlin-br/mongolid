<?php
namespace Mongolid\Util;

use DateTime;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;

/**
 * This class is responsible for converting UTCDateTime from MongoDB driver in
 * local date time.
 *
 * It will be unnecessary once MongoDB driver fixes a know issue in UTCDateTime.
 * The proposal was created, for more information:
 *
 * @see https://jira.mongodb.org/browse/PHPC-760
 */
class LocalDateTime
{
    /**
     * Retrieves DateTime instance using default timezone.
     */
    public static function get(UTCDateTime $date): DateTime
    {
        $date = $date->toDateTime();
        $date->setTimezone(new DateTimeZone(date_default_timezone_get()));

        return $date;
    }

    /**
     * Retrieves formatted date time using timezone.
     */
    public static function format(
        UTCDateTime $date,
        string $format = 'd/m/Y H:i:s'
    ): string {
        return self::get($date)->format($format);
    }

    /**
     * Retrieves timestamp using timezone.
     */
    public static function timestamp(UTCDateTime $date): int
    {
        return self::get($date)->getTimestamp();
    }
}
