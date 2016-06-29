<?php
namespace Mongolid\Serializer\Type;

use DateTime;
use Mockery as m;
use MongoDB\BSON\UTCDateTime as MongoUTCDateTime;
use Mongolid\Serializer\SerializableTypeInterface;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test case for UTCDateTime class
 */
class UTCDateTimeTest extends TestCase
{
    /**
     * @var integer
     */
    protected $timestamp;

    /**
     * @var MongoUTCDateTime
     */
    protected $mongoDate;

    /**
     * @var UTCDateTime
     */
    protected $dateTime;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->timestamp = time();
        $this->mongoDate = new MongoUTCDateTime($this->timestamp*1000);
        $this->dateTime  = new UTCDateTime($this->timestamp);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        m::close();
        parent::tearDown();
        unset($this->timestamp);
        unset($this->mongoDate);
        unset($this->dateTime);
    }

    public function testUtcDateTimeShouldBeSerializable()
    {
        $this->assertInstanceOf(
            SerializableTypeInterface::class,
            $this->dateTime
        );
    }

    public function testConstructorUsingTimestampShouldSetMongoDateAndTimestamp()
    {
        $this->assertAttributeEquals(
            $this->timestamp*1000,
            'timestamp',
            $this->dateTime
        );
        $this->assertAttributeEquals(
            $this->mongoDate,
            'mongoDate',
            $this->dateTime
        );
    }

    public function testConstructorUsingMongoDateShouldSetMongoDateAndTimestamp()
    {
        $this->assertAttributeEquals(
            $this->timestamp*1000,
            'timestamp',
            new UTCDateTime($this->mongoDate)
        );
        $this->assertAttributeEquals(
            $this->mongoDate,
            'mongoDate',
            new UTCDateTime($this->mongoDate)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Invalid argument type given. Constructor allows
     *                           only integer or MongoDB\BSON\UTCDateTime
     */
    public function testConstrucorWithInvalidParameterShouldThrowException()
    {
        new UTCDateTime('invalid-parameter');
    }

    public function testUnserializeShouldKeepFormatedDate()
    {
        $date = unserialize(serialize($this->dateTime));

        $this->assertAttributeEquals($this->mongoDate, 'mongoDate', $date);
    }

    public function testConvertShouldRetrieveMongodbUtcDateTime()
    {
        $this->assertEquals($this->mongoDate, $this->dateTime->convert());
    }

    public function testCallUndefinedMethodOfUtcDateTimeShouldCallMongoUtcDateTime()
    {
        $date      = new DateTime();
        $timestamp = $date->getTimestamp();
        $mongoDate = new MongoUTCDateTime($timestamp*1000);

        $this->assertEquals($date, (new UTCDateTime($timestamp))->toDateTime());
    }
}
