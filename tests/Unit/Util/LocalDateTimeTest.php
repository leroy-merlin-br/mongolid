<?php
namespace Mongolid\Util;

use DateTime;
use DateTimeZone;
use MongoDB\BSON\UTCDateTime;
use Mongolid\TestCase;

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
    protected function setUp(): void
    {
        parent::setUp();

        $this->date = new DateTime('01/05/2017 15:40:00');
        $this->date->setTimezone(new DateTimeZone('UTC'));

        date_default_timezone_set('America/Sao_Paulo');
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        unset($this->date);
        parent::tearDown();
    }

    public function testGetShouldRetrievesDateUsingTimezone(): void
    {
        // Set
        $date = new UTCDateTime($this->date);

        // Actions
        $result = LocalDateTime::get($date);

        // Assertions
        $this->assertEquals($this->date, $result);
    }

    public function testFormatShouldRetrievesDateWithDefaultFormat(): void
    {
        // Set
        $timezone = new DateTimeZone(date_default_timezone_get());
        $this->date->setTimezone($timezone);

        // Actions
        $result = LocalDateTime::format(new UTCDateTime($this->date));

        // Assertions
        $this->assertSame($this->date->format($this->format), $result);
    }

    public function testFormatShouldRetrieveDateUsingGivenFormat(): void
    {
        // Set
        $timezone = new DateTimeZone(date_default_timezone_get());
        $this->date->setTimezone($timezone);
        $format = 'Y-m-d H:i:s';

        // Actions
        $result = LocalDateTime::format(new UTCDateTime($this->date), $format);

        // Assertions
        $this->assertSame($this->date->format($format), $result);
    }

    public function testTimestampShouldRetrievesTimestampUsingTimezone(): void
    {
        // Set
        $timestamp = $this->date->getTimestamp();
        $date = new UTCDateTime($this->date);

        // Actions
        $mongoDateTimestamp = LocalDateTime::timestamp($date);

        // Assertions
        $this->assertSame(
            DateTime::createFromFormat($timestamp, $this->format),
            DateTime::createFromFormat($mongoDateTimestamp, $this->format)
        );
    }
}
