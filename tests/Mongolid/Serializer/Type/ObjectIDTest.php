<?php
namespace Mongolid\Serializer\Type;

use MongoDB\BSON\ObjectID as MongoObjectID;
use Mongolid\Serializer\SerializableTypeInterface;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * Test case for ObjectID class
 */
class ObjectIDTest extends TestCase
{
    /**
     * @var string
     */
    protected $stringId = '000000000000000000001234';

    /**
     * @var MongoObjectID
     */
    protected $mongoId;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->mongoId = new MongoObjectID($this->stringId);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        parent::tearDown();
        unset($this->stringId);
        unset($this->mongoId);
    }

    public function testObjectIDShouldBeSerializable()
    {
        $this->assertInstanceOf(
            SerializableTypeInterface::class,
            new ObjectID($this->mongoId)
        );
    }

    public function testConstructorShouldCastMongoDBObjectIDToString()
    {
        $this->assertAttributeEquals(
            $this->stringId,
            'id',
            new ObjectID($this->mongoId)
        );
    }

    public function testSerializeShouldConvertObjectIDToString()
    {
        $objectId = unserialize(serialize(new ObjectID($this->mongoId)));

        $this->assertAttributeEquals($this->stringId, 'id', $objectId);
    }

    public function testConvertShouldRetrieveMongoDBObjectID()
    {
        $objectId = new ObjectID($this->mongoId);
        $this->assertEquals($this->mongoId, $objectId->convert());
    }
}
