<?php

namespace Mongolid\Util;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use PHPUnit_Framework_TestCase as TestCase;

class LocalDateTimeTest extends TestCase
{
    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var string
     */
    protected $format = 'd/m/Y H:i:s';

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        date_default_timezone_set('UTC');

        $this->date = new DateTime('01/05/2017 15:40:00');

        date_default_timezone_set('Europe/Prague');
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->date, $this->format);
    }

    public function testGetShouldRetrievesDateUsingTimezone()
    {
        $this->assertEquals(
            $this->date->format($this->format),
            LocalDateTime::get(new UTCDateTime($this->date))->format(
                $this->format
            )
        );
    }

    public function testFormatShouldRetrievesDateWithDefaultFormat()
    {
        $this->assertEquals(
            $this->date->format($this->format),
            LocalDateTime::format(new UTCDateTime($this->date))
        );
    }

    public function testFormatShouldRetrieesDateUsingGivenFormat()
    {
        $format = 'Y-m-d H:i:s';

        $this->assertEquals(
            $this->date->format($format),
            LocalDateTime::format(new UTCDateTime($this->date), $format)
        );
    }

    public function testTimestampShouldRetrievesTimestampUsingTimezone()
    {
        $dateTimestamp = $this->date->getTimestamp();
        $mongoDateTimestamp = LocalDateTime::timestamp(
            new UTCDateTime($this->date)
        );

        $this->assertEquals(
            DateTime::createFromFormat($dateTimestamp, $this->format),
            DateTime::createFromFormat($mongoDateTimestamp, $this->format)
        );
    }
}
