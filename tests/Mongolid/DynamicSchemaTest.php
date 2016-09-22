<?php

namespace Mongolid;

use Mockery as m;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\Schema;
use TestCase;

class DynamicSchemaTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

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
