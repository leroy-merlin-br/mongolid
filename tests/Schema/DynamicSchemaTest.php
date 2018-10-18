<?php
namespace Mongolid\Schema;

use Mockery as m;
use Mongolid\TestCase;

class DynamicSchemaTest extends TestCase
{
    public function testShouldExtendSchema()
    {
        // Arrange
        $schema = m::mock(DynamicSchema::class.'[]');

        // Assert
        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function testShouldBeDynamic()
    {
        // Arrange
        $schema = m::mock(DynamicSchema::class.'[]');

        // Assert
        $this->assertAttributeEquals(true, 'dynamic', $schema);
    }
}
