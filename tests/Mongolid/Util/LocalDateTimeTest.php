<?php

namespace Mongolid\Util;

use DateTime;
use DateTimeZone;
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

        $this->date = new DateTime('01/05/2017 15:40:00');
        $this->date->setTimezone(new DateTimeZone('UTC'));

        date_default_timezone_set('America/Sao_Paulo');
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
            $this->date,
            LocalDateTime::get(new UTCDateTime($this->date))
        );
    }

    public function testFormatShouldRetrievesDateWithDefaultFormat()
    {
        $this->date->setTimezone(
            new DateTimeZone(date_default_timezone_get())
        );

        $this->assertEquals(
            $this->date->format($this->format),
            LocalDateTime::format(new UTCDateTime($this->date))
        );
    }

    public function testFormatShouldRetrieesDateUsingGivenFormat()
    {
        $this->date->setTimezone(
            new DateTimeZone(date_default_timezone_get())
        );

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
