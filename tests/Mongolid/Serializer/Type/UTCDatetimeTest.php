<?php
namespace Mongolid\Serializer\Type;

use DateTime;
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
    protected $formatedDate = '1990-02-20 21:45:00';

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
        $now = DateTime::createFromFormat('Y-m-d H:i:s', $this->formatedDate);
        $this->mongoDate = new MongoUTCDateTime($now->getTimestamp()*1000);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->formatedDate);
        unset($this->mongoDate);
    }

    public function testUtcDateTimeShouldBeSerializable()
    {
        $this->assertInstanceOf(
            SerializableTypeInterface::class,
            new UTCDateTime($this->mongoDate)
        );
    }

    public function testConstructorShouldCastMongodbUtcDateTimeToString()
    {
        $this->assertAttributeEquals(
            $this->formatedDate,
            'date',
            new UTCDateTime($this->mongoDate)
        );
    }

    public function testUnserializeShouldKeepFormatedDate()
    {
        $date = unserialize(serialize(new UTCDateTime($this->mongoDate)));

        $this->assertAttributeEquals($this->formatedDate, 'date', $date);
    }

    public function testConvertShouldRetrieveMongodbUtcDateTime()
    {
        $date = new UTCDateTime($this->mongoDate);
        $this->assertEquals($this->mongoDate, $date->convert());
    }
}
