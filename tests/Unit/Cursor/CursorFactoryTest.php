<?php

namespace Mongolid\Cursor;

use Mockery as m;
use MongoDB\Collection;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;

class CursorFactoryTest extends TestCase
{
    public function testShouldCreateACursor()
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

        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertNotInstanceOf(CacheableCursor::class, $result);
        $this->assertNotInstanceOf(EmbeddedCursor::class, $result);
        $this->assertEquals($schema, $result->entitySchema);
    }

    public function testShouldCreateACacheableCursor()
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

        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertInstanceOf(CacheableCursor::class, $result);
        $this->assertNotInstanceOf(EmbeddedCursor::class, $result);
        $this->assertEquals($schema, $result->entitySchema);
    }

    public function testShouldCreateAEmbeddedCursor()
    {
        // Set
        $factory = new CursorFactory();
        $entityClass = 'MyModelClass';

        // Assert
        $result = $factory->createEmbeddedCursor($entityClass, [['foo' => 'bar']]);

        $this->assertInstanceOf(EmbeddedCursor::class, $result);
        $this->assertNotInstanceOf(Cursor::class, $result);
        $this->assertNotInstanceOf(CacheableCursor::class, $result);
        $this->assertEquals($entityClass, $result->entityClass);
    }
}
