<?php
namespace Mongolid;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;
use MongoDB\BSON\ObjectID;
use MongoDB\Driver\Exception\Exception as MongoException;

class SchemaTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldNotBeDynamicByDefault()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');

        // Assert
        $this->assertAttributeEquals(false, 'dynamic', $schema);
    }

    public function testShouldCastNullIntoObjectId()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');
        $value  = null;

        // Assert
        $this->assertInstanceOf(
            ObjectID::class,
            $schema->objectId($value)
        );
    }

    public function testShouldNotCastRandomStringIntoObjectId()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');
        $value  = 'A random string';

        // Assert
        $this->assertEquals(
            $value,
            $schema->objectId($value)
        );
    }

    public function testShouldCastObjectIdStringIntoObjectId()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');
        $value  = '507f1f77bcf86cd799439011';

        // Assert
        $this->assertInstanceOf(
            ObjectID::class,
            $schema->objectId($value)
        );

        $this->assertEquals(
            $value,
            (string)$schema->objectId($value)
        );
    }
}
