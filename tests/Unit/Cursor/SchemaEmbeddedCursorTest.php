<?php

namespace Mongolid\Cursor;

use stdClass;
use Mongolid\TestCase;

class SchemaEmbeddedCursorTest extends TestCase
{
    public function testShouldLimitDocumentQuantity(): void
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
        $this->assertEquals(
            [
                ['name' => 'A'],
                ['name' => 'B'],
            ],
            $cursor->toArray()
        );
    }

    /**
     * @dataProvider getDocumentsToSort
     */
    public function testShouldSortDocuments(array $items, array $parameters, array $expected): void
    {
        // Arrange
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $cursor->sort($parameters);
        $this->assertSame(
            $expected,
            $cursor->toArray()
        );
    }

    public function testShouldSkipDocuments(): void
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
        $this->assertEquals(
            [
                ['name' => 'C'],
            ],
            $cursor->toArray()
        );
    }

    public function testShouldCountDocuments(): void
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

    public function testShouldCountDocumentsWithCountFunction(): void
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $this->assertEquals(3, count($cursor));
    }

    public function testShouldRewind(): void
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->rewind();
        $this->assertEquals(0, $cursor->key());
    }

    public function testShouldNotGetCurrentWhenCursorIsInvalid(): void
    {
        // Arrange
        $items = [];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $entity = $cursor->current();
        $this->assertNull($entity);
    }

    public function testShouldGetCurrentUsingEntityClass(): void
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
        $this->assertEquals('A', $entity->name);
    }

    public function testShouldGetAllInArrayFormat(): void
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

    public function testShouldImplementKeyMethodFromIterator(): void
    {
        // Arrange
        $cursor = $this->getCursor();

        $this->setProtected($cursor, 'position', 7);

        // Assertion
        $this->assertEquals(7, $cursor->key());
    }

    public function testShouldImplementNextMethodFromIterator(): void
    {
        // Arrange
        $cursor = $this->getCursor();

        $this->setProtected($cursor, 'position', 7);

        // Assertion
        $cursor->next();
        $this->assertEquals(8, $cursor->key());
    }

    public function testShouldImplementValidMethodFromIterator(): void
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

    public function getDocumentsToSort(): array
    {
        $age24 = (object) ['age' => 24];

        return [
            'one sorting parameter ASC' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                ],
                'parameters' => ['age' => 1],
                'expected' => [
                    $age24,
                    ['age' => 25],
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                ],
            ],
            'one sorting parameter DESC' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                ],
                'parameters' => ['age' => -1],
                'expected' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                    ['age' => 25],
                    $age24,
                ],
            ],
            'two sorting parameters' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                ],
                'parameters' => ['age' => 1, 'name' => -1],
                'expected' => [
                    $age24,
                    ['age' => 25],
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                    ['age' => 26, 'name' => 'Abe'],
                ],
            ],
            'three sorting parameters' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe', 'color' => 'red'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Zizaco', 'color' => 'red'],
                    ['age' => 26, 'name' => 'Zizaco', 'color' => 'blue'],
                    ['age' => 26, 'name' => 'John'],
                ],
                'parameters' => ['age' => 1, 'name' => -1, 'color' => 1],
                'expected' => [
                    $age24,
                    ['age' => 25],
                    ['age' => 26, 'name' => 'Zizaco', 'color' => 'blue'],
                    ['age' => 26, 'name' => 'Zizaco', 'color' => 'red'],
                    ['age' => 26, 'name' => 'John'],
                    ['age' => 26, 'name' => 'Abe', 'color' => 'red'],
                ],
            ],
        ];
    }

    protected function getCursor(
        $entityClass = stdClass::class,
        $items = []
    ): SchemaEmbeddedCursor {
        return new SchemaEmbeddedCursor($entityClass, $items);
    }
}
