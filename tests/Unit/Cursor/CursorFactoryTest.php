<?php

namespace Mongolid\Cursor;

use Mockery as m;
use MongoDB\Collection;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;

class CursorFactoryTest extends TestCase
{
    public function testShouldCreateACursor(): void
    {
        // Set
        $factory = new CursorFactory();
        $schema = m::mock(Schema::class);
        $collection = m::mock(Collection::class);

        // Assert
        $result = $factory->createCursor(
            $schema,
            $collection,
            'find',
            ['age' => ['$gr' => 25]]
        );

        $this->assertInstanceOf(SchemaCursor::class, $result);
        $this->assertNotInstanceOf(SchemaCacheableCursor::class, $result);
        $this->assertNotInstanceOf(SchemaEmbeddedCursor::class, $result);
        $this->assertEquals($schema, $result->entitySchema);
    }

    public function testShouldCreateACacheableCursor(): void
    {
        // Set
        $factory = new CursorFactory();
        $schema = m::mock(Schema::class);
        $collection = m::mock(Collection::class);

        // Assert
        $result = $factory->createCursor(
            $schema,
            $collection,
            'find',
            $params = ['age' => ['$gr' => 25]],
            true // $cacheable
        );

        $this->assertInstanceOf(SchemaCursor::class, $result);
        $this->assertInstanceOf(SchemaCacheableCursor::class, $result);
        $this->assertNotInstanceOf(SchemaEmbeddedCursor::class, $result);
        $this->assertEquals($schema, $result->entitySchema);
    }

    public function testShouldCreateAEmbeddedCursor(): void
    {
        // Set
        $factory = new CursorFactory();
        $entityClass = 'MyModelClass';

        // Assert
        $result = $factory->createEmbeddedCursor(
            $entityClass,
            [['foo' => 'bar']]
        );

        $this->assertInstanceOf(SchemaEmbeddedCursor::class, $result);
        $this->assertNotInstanceOf(SchemaCursor::class, $result);
        $this->assertNotInstanceOf(SchemaCacheableCursor::class, $result);
        $this->assertEquals($entityClass, $result->entityClass);
    }
}
