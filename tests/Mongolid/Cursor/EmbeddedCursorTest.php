<?php

namespace Mongolid\Cursor;

use Mockery as m;
use Mongolid\ActiveRecord;
use Mongolid\Model\PolymorphableInterface;
use stdClass;
use TestCase;

class EmbeddedCursorTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldLimitDocumentQuantity()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $cursor->limit(2);
        $this->assertAttributeEquals(
            [
                ['name' => 'A'],
                ['name' => 'B'],
            ],
            'items',
            $cursor
        );
    }

    public function testShouldSortDocuments()
    {
        // Arrange
        $items = [
            ['age' => 26, 'name' => 'Abe'],
            ['age' => 25],
            (object) ['age' => 24],
            ['age' => 26, 'name' => 'Zizaco'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $cursor->sort(['age' => 1, 'name' - 1]);
        $this->assertAttributeEquals(
            [
                (object) ['age' => 24],
                ['age' => 25],
                ['age' => 26, 'name' => 'Zizaco'],
                ['age' => 26, 'name' => 'Abe'],
            ],
            'items',
            $cursor
        );
    }

    public function testShouldSkipDocuments()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $cursor->skip(2);
        $this->assertAttributeEquals(
            [
                ['name' => 'C'],
            ],
            'items',
            $cursor
        );
    }

    public function testShouldCountDocuments()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $this->assertEquals(3, $cursor->count());
    }

    public function testShouldRewind()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->rewind();
        $this->assertAttributeEquals(0, 'position', $cursor);
    }

    public function testShouldGetCurrent()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $entity = $cursor->current();
        $this->assertInstanceOf(stdClass::class, $entity);
        $this->assertAttributeEquals('B', 'name', $entity);
    }

    public function testShouldNotGetCurrentWhenCursorIsInvalid()
    {
        // Arrange
        $items = [];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $entity = $cursor->current();
        $this->assertNull($entity);
    }

    public function testShouldGetCurrentUsingEntityClass()
    {
        // Arrange
        $object = new stdClass();
        $object->name = 'A';
        $items = [$object];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 0);

        // Assert
        $entity = $cursor->current();
        $this->assertInstanceOf(stdClass::class, $entity);
        $this->assertAttributeEquals('A', 'name', $entity);
    }

    public function testShouldGetCurrentUsingEntityClassAndMorphinIt()
    {
        // Arrange
        $object = new class() extends ActiveRecord implements PolymorphableInterface {
            public function polymorph()
            {
                return 'Bacon';
            }
        };

        $class = get_class($object);
        $items = [$object->attributes];
        $cursor = $this->getCursor($class, $items);

        $this->setProtected($cursor, 'position', 0);

        // Assert
        $entity = $cursor->current();
        $this->assertEquals('Bacon', $entity);
    }

    public function testShouldGetFirst()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $entity = $cursor->first();
        $this->assertInstanceOf(stdClass::class, $entity);
        $this->assertAttributeEquals('A', 'name', $entity);
    }

    public function testShouldGetAllItems()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        $entityA = new stdClass();
        $entityA->name = 'A';

        $entityB = new stdClass();
        $entityB->name = 'B';

        $expected = [
            $entityA,
            $entityB,
        ];

        // Assert
        $result = $cursor->all();

        $this->assertEquals($expected, $result);
    }

    public function testShouldGetAllInArrayFormat()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $result = $cursor->toArray();
        $this->assertEquals($items, $result);
    }

    public function testShouldImplementKeyMethodFromIterator()
    {
        // Arrange
        $cursor = $this->getCursor();

        $this->setProtected($cursor, 'position', 7);

        // Assertion
        $this->assertEquals(7, $cursor->key());
    }

    public function testShouldImplementNextMethodFromIterator()
    {
        // Arrange
        $cursor = $this->getCursor();

        $this->setProtected($cursor, 'position', 7);

        // Assertion
        $cursor->next();
        $this->assertAttributeEquals(8, 'position', $cursor);
    }

    public function testShouldImplementValidMethodFromIterator()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $this->assertTrue($cursor->valid());
        $this->setProtected($cursor, 'position', 8);
        $this->assertFalse($cursor->valid());
    }

    protected function getCursor(
        $entityClass = stdClass::class,
        $items = []
    ) {
        return new EmbeddedCursor($entityClass, $items);
    }
}
