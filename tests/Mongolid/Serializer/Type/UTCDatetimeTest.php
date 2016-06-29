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
     * @var string
     */
    protected $formattedDate = '1990-02-20 21:45:00';

    /**
     * @var integer
     */
    protected $timestamp;

    /**
     * @var MongoUTCDateTime
     */
    protected $mongoDate;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $this->formattedDate);
        $this->timestamp = $date->getTimestamp();
        $this->mongoDate = new MongoUTCDateTime($this->timestamp*1000);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        m::close();
        parent::tearDown();
        unset($this->formattedDate);
        unset($this->mongoDate);
    }

    public function testUtcDateTimeShouldBeSerializable()
    {
        $this->assertInstanceOf(
            SerializableTypeInterface::class,
            new UTCDateTime($this->timestamp)
        );
    }

    public function testConstructorUsingTimestampShouldSetMongoDateAndTimestamp()
    {
        $this->assertAttributeEquals(
            $this->timestamp*1000,
            'timestamp',
            new UTCDateTime($this->timestamp)
        );
        $this->assertAttributeEquals(
            $this->mongoDate,
            'mongoDate',
            new UTCDateTime($this->timestamp)
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
        $date = unserialize(serialize(new UTCDateTime($this->timestamp)));

        $this->assertAttributeEquals($this->mongoDate, 'mongoDate', $date);
    }

    public function testConvertShouldRetrieveMongodbUtcDateTime()
    {
        $date = new UTCDateTime($this->timestamp);
        $this->assertEquals($this->mongoDate, $date->convert());
    }

    public function testCallUndefinedMethodOfUtcDateTimeShouldCallMongoUtcDateTime()
    {
        $date      = new DateTime();
        $timestamp = $date->getTimestamp();
        $mongoDate = new MongoUTCDateTime($timestamp*1000);

        $this->assertEquals($date, (new UTCDateTime($timestamp))->toDateTime());
    }
}
