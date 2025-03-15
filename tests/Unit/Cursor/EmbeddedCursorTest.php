<?php

namespace Mongolid\Cursor;

use Mongolid\Model\AbstractModel;
use Mongolid\TestCase;
use stdClass;

final class EmbeddedCursorTest extends TestCase
{
    public function testShouldLimitDocumentQuantity(): void
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = new EmbeddedCursor($items);

        // Actions
        $cursor->limit(2);
        $result = $this->getProtected($cursor, 'items');

        // Assertions
        $this->assertSame([['name' => 'A'], ['name' => 'B']], $result);
    }

    /**
     * @dataProvider getDocumentsToSort
     */
    public function testShouldSortDocuments(array $items, array $parameters, array $expected): void
    {
        // Set
        $cursor = new EmbeddedCursor($items);

        // Actions
        $cursor->sort($parameters);
        $result = $this->getProtected($cursor, 'items');

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldSkipDocuments(): void
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = new EmbeddedCursor($items);

        // Actions
        $cursor->skip(2);
        $result = $this->getProtected($cursor, 'items');

        // Assertions
        $this->assertSame([['name' => 'C']], $result);
    }

    public function testShouldCountDocuments(): void
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = new EmbeddedCursor($items);

        // Actions
        $result = $cursor->count();

        // Assertions
        $this->assertSame(3, $result);
    }

    public function testShouldCountDocumentsWithCountFunction(): void
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = new EmbeddedCursor($items);

        // Actions
        $result = count($cursor);

        // Assertions
        $this->assertSame(3, $result);
    }

    public function testShouldRewind(): void
    {
        // Set
        $cursor = new EmbeddedCursor([]);

        // Actions
        $cursor->rewind();
        $result = $this->getProtected($cursor, 'position');

        // Assertions
        $this->assertSame(0, $result);
    }

    public function testShouldGetCurrent(): void
    {
        // Set
        $object = new class extends AbstractModel
        {
        };
        $class = $object::class;
        $itemA = new $class();
        $itemA->name = 'A';

        $itemB = new $class();
        $itemB->name = 'B';

        $itemC = new $class();
        $itemC->name = 'C';

        $items = [
            $itemA,
            $itemB,
            $itemC,
        ];
        $cursor = new EmbeddedCursor($items);

        $this->setProtected($cursor, 'position', 1);

        // Actions
        $model = $cursor->current();

        // Assertions
        $this->assertInstanceOf($class, $model);
        $this->assertSame('B', $model->name);
    }

    public function testShouldNotGetCurrentWhenCursorIsInvalid(): void
    {
        // Set
        $items = [];
        $cursor = new EmbeddedCursor($items);

        $this->setProtected($cursor, 'position', 1);

        // Actions
        $model = $cursor->current();

        // Assertions
        $this->assertNull($model);
    }

    public function testShouldGetCurrentUsingModelClass(): void
    {
        // Set
        $object = new stdClass();
        $object->name = 'A';
        $items = [$object];
        $cursor = new EmbeddedCursor($items);

        $this->setProtected($cursor, 'position', 0);

        // Actions
        $model = $cursor->current();

        // Assertions
        $this->assertInstanceOf(stdClass::class, $model);
        $this->assertSame('A', $model->name);
    }

    public function testShouldGetCurrentUsingModelClassMorphingIt(): void
    {
        // Set
        $model = new class () extends AbstractModel
        {
        };
        $model->name = 'John';
        $model->syncOriginalDocumentAttributes();

        $items = [$model];
        $cursor = new EmbeddedCursor($items);

        // Actions
        $result = $cursor->current();

        // Assertions
        $this->assertSame($model, $result);
        $this->assertSame('John', $result->name);
    }

    public function testShouldGetFirst(): void
    {
        // Set
        $object = new class extends AbstractModel
        {
        };
        $class = $object::class;
        $modelA = new $class();
        $modelA->name = 'A';
        $modelA->syncOriginalDocumentAttributes();
        $modelB = clone $modelA;
        $modelB->name = 'B';
        $modelB->syncOriginalDocumentAttributes();

        $items = [
            $modelA,
            $modelB,
        ];
        $cursor = new EmbeddedCursor($items);

        $this->setProtected($cursor, 'position', 1);

        // Actions
        $model = $cursor->first();

        // Assertions
        $this->assertInstanceOf($class, $model);
        $this->assertSame('A', $model->name);
    }

    public function testShouldGetAllItems(): void
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
        $cursor = new EmbeddedCursor($items);
        $this->setProtected($cursor, 'position', 1);

        $expected = [
            $modelA,
            $modelB,
        ];

        // Actions
        $result = $cursor->all();

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldGetAllInArrayFormat(): void
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = new EmbeddedCursor($items);
        $this->setProtected($cursor, 'position', 1);

        // Actions
        $result = $cursor->toArray();

        // Assertions
        $this->assertSame($items, $result);
    }

    public function testShouldImplementKeyMethodFromIterator(): void
    {
        // Set
        $cursor = new EmbeddedCursor([]);
        $this->setProtected($cursor, 'position', 7);

        // Actions
        $result = $cursor->key();

        // Assertions
        $this->assertSame(7, $result);
    }

    public function testShouldImplementNextMethodFromIterator(): void
    {
        // Set
        $cursor = new EmbeddedCursor([]);
        $this->setProtected($cursor, 'position', 7);

        // Actions
        $cursor->next();
        $result = $this->getProtected($cursor, 'position');

        // Assertions
        $this->assertSame(8, $result);
    }

    public function testShouldImplementValidMethodFromIterator(): void
    {
        // Set
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = new EmbeddedCursor($items);

        // Actions
        $result = $cursor->valid();

        // Assertions
        $this->assertTrue($result);

        // Actions
        $this->setProtected($cursor, 'position', 8);
        $result = $cursor->valid();

        // Assertions
        $this->assertFalse($result);
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
                    ['age' => 26, 'name' => 'Wilson'],
                    ['age' => 26, 'name' => 'John'],
                    [],
                ],
                'parameters' => ['age' => 1],
                'expected' => [
                    [],
                    $age24,
                    ['age' => 25],
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 26, 'name' => 'Wilson'],
                    ['age' => 26, 'name' => 'John'],
                ],
            ],
            'one sorting parameter DESC' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Wilson'],
                    ['age' => 26, 'name' => 'John'],
                    [],
                ],
                'parameters' => ['age' => -1],
                'expected' => [
                    ['age' => 26, 'name' => 'Abe'],
                    ['age' => 26, 'name' => 'Wilson'],
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
                    ['age' => 26, 'name' => 'Wilson'],
                    ['age' => 26, 'name' => 'John'],
                    [],
                ],
                'parameters' => ['age' => 1, 'name' => -1],
                'expected' => [
                    [],
                    $age24,
                    ['age' => 25],
                    ['age' => 26, 'name' => 'Wilson'],
                    ['age' => 26, 'name' => 'John'],
                    ['age' => 26, 'name' => 'Abe'],
                ],
            ],
            'three sorting parameters' => [
                'items' => [
                    ['age' => 26, 'name' => 'Abe', 'color' => 'red'],
                    ['age' => 25],
                    $age24,
                    ['age' => 26, 'name' => 'Wilson', 'color' => 'red'],
                    ['age' => 26, 'name' => 'Wilson', 'color' => 'blue'],
                    ['age' => 26, 'name' => 'John'],
                ],
                'parameters' => ['age' => 1, 'name' => -1, 'color' => 1],
                'expected' => [
                    $age24,
                    ['age' => 25],
                    ['age' => 26, 'name' => 'Wilson', 'color' => 'blue'],
                    ['age' => 26, 'name' => 'Wilson', 'color' => 'red'],
                    ['age' => 26, 'name' => 'John'],
                    ['age' => 26, 'name' => 'Abe', 'color' => 'red'],
                ],
            ],
        ];
    }
}
