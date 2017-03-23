<?php

namespace Mongolid\Util;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use PHPUnit_Framework_TestCase as TestCase;

class LocalDateTimeTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        date_default_timezone_set('UTC');
    }

    public function testGetShouldRetrievesDateUsingTimezone()
    {
        $format = 'd/m/Y H:i:s';
        $date = new DateTime('01/05/2017 15:40:00');

        date_default_timezone_set('Brazil/Acre');

        $this->assertEquals(
            $date->format($format),
            LocalDateTime::get(new UTCDateTime($date))->format($format)
        );
    }

    public function testFormatShouldRetrievesDateWithDefaultFormat()
    {
        $format = 'd/m/Y H:i:s';
        $date = new DateTime('01/05/2017 15:40:00');

        date_default_timezone_set('Brazil/Acre');

        $this->assertEquals(
            $date->format($format),
            LocalDateTime::format(new UTCDateTime($date))
        );
    }

    public function testFormatShouldRetrieesDateUsingGivenFormat()
    {
        $format = 'Y-m-d H:i:s';
        $date = new DateTime('01/05/2017 15:40:00');

        date_default_timezone_set('Brazil/Acre');

        $this->assertEquals(
            $date->format($format),
            LocalDateTime::format(new UTCDateTime($date), $format)
        );
    }
}
