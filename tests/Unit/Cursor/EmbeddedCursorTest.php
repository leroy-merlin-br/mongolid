<?php
namespace Mongolid\Cursor;

use Mongolid\Model\AbstractModel;
use Mongolid\Model\PolymorphableInterface;
use Mongolid\TestCase;
use stdClass;

class EmbeddedCursorTest extends TestCase
{
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

    /**
     * @dataProvider getDocumentsToSort
     */
    public function testShouldSortDocuments($items, $parameters, $expected)
    {
        // Arrange
        $cursor = $this->getCursor(stdClass::class, $items);

        // Assert
        $cursor->sort($parameters);
        $this->assertAttributeSame(
            $expected,
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

    public function testShouldCountDocumentsWithCountFunction()
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
        $object = new class extends AbstractModel
        {
        };
        $class = get_class($object);
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor($class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $model = $cursor->current();
        $this->assertInstanceOf($class, $model);
        $this->assertSame('B', $model->name);
    }

    public function testShouldNotGetCurrentWhenCursorIsInvalid()
    {
        // Arrange
        $items = [];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $model = $cursor->current();
        $this->assertNull($model);
    }

    public function testShouldGetCurrentUsingModelClass()
    {
        // Arrange
        $object = new stdClass();
        $object->name = 'A';
        $items = [$object];
        $cursor = $this->getCursor(stdClass::class, $items);

        $this->setProtected($cursor, 'position', 0);

        // Assert
        $model = $cursor->current();
        $this->assertInstanceOf(stdClass::class, $model);
        $this->assertAttributeEquals('A', 'name', $model);
    }

    public function testShouldGetCurrentUsingModelClassAndMorphingIt()
    {
        // Arrange
        $object = new class() extends AbstractModel implements PolymorphableInterface
        {
            public function polymorph()
            {
                return $this;
            }
        };
        $object->name = 'John';
        $object->syncOriginalDocumentAttributes();

        $class = get_class($object);
        $items = [$object->getDocumentAttributes()];
        $cursor = $this->getCursor($class, $items);

        // Actions
        $model = $cursor->current();

        // Assertions
        $this->assertEquals($object, $model);
        $this->assertSame('John', $model->name);
    }

    public function testShouldGetFirst()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $object = new class extends AbstractModel
        {
        };
        $class = get_class($object);
        $cursor = $this->getCursor($class, $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $model = $cursor->first();
        $this->assertInstanceOf($class, $model);
        $this->assertSame('A', $model->name);
    }

    public function testShouldGetAllItems()
    {
        // Set
        $modelA = new class extends AbstractModel
        {
        };
        $modelA->name = 'A';
        $modelA->syncOriginalDocumentAttributes();
        $modelB = clone $modelA;
        $modelB->name = 'B';
        $modelB->syncOriginalDocumentAttributes();

        $items = [
            $modelA,
            $modelB,
        ];
        $cursor = $this->getCursor(get_class($modelA), $items);
        $this->setProtected($cursor, 'position', 1);

        $expected = [
            $modelA,
            $modelB,
        ];

        // Assert
        $result = $cursor->all();

        $this->assertEquals($expected, $result);
    }

    public function testShouldGetAllItemsWithBackwardsCompatibility()
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
        ];

        $modelA = new class extends AbstractModel
        {
        };
        $modelA->name = 'A';
        $modelA->syncOriginalDocumentAttributes();
        $modelB = clone $modelA;
        $modelB->name = 'B';
        $modelB->syncOriginalDocumentAttributes();

        $cursor = $this->getCursor(get_class($modelA), $items);
        $this->setProtected($cursor, 'position', 1);

        $expected = [
            $modelA,
            $modelB,
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

    public function getDocumentsToSort()
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
                    [],
                ],
                'parameters' => ['age' => 1],
                'expected' => [
                    [],
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
                    [],
                ],
                'parameters' => ['age' => -1],
                'expected' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                    ['age' => 25],
                    $age24,
                    [],
                ],
            ],
            'two sorting parameters' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Zizaco'],
                    ['age' => 26, 'name' => 'John'],
                    [],
                ],
                'parameters' => ['age' => 1, 'name' => -1],
                'expected' => [
                    [],
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
        $modelClass = stdClass::class,
        $items = []
    ) {
        return new EmbeddedCursor($modelClass, $items);
    }
}
