<?php

namespace Mongolid;

use Mockery as m;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;

class DynamicSchemaTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldExtendSchema()
    {
        // Arrange
        $schema = new DynamicSchema();

        // Assert
        $this->assertInstanceOf(Schema::class, $schema);
    }

    public function testShouldBeDynamic()
    {
        // Arrange
        $schema = new DynamicSchema();

        // Assert
        $this->assertAttributeEquals(true, 'dynamic', $schema);
    }
}
