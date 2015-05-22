<?php
namespace Mongolid;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

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

    public function testShouldCastNullIntoMongoId()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');
        $value  = null;

        // Assert
        $this->assertInstanceOf(
            'MongoId',
            $schema->mongoId($value)
        );
    }

    public function testShouldNotCastRandomStringIntoMongoId()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');
        $value  = 'A random string';

        // Act
        $this->setExpectedException(
          'MongoException', 'Invalid object ID'
        );

        // Assert
        $schema->mongoId($value);
    }

    public function testShouldCastObjectIdStringIntoMongoId()
    {
        // Arrange
        $schema = m::mock('Mongolid\Schema[]');
        $value  = '507f1f77bcf86cd799439011';

        // Assert
        $this->assertInstanceOf(
            'MongoId',
            $schema->mongoId($value)
        );

        $this->assertEquals(
            $value,
            (string)$schema->mongoId($value)
        );
    }
}
