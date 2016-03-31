<?php

namespace Mongolid\Cursor;

use Mockery as m;
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
        $cursor = $this->getCursor('stdClass', $items);

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
        $this->markTestSkipped();
    }

    public function testShouldSkipDocuments()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor('stdClass', $items);

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
        $cursor = $this->getCursor('stdClass', $items);

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
        $cursor = $this->getCursor('stdClass', $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $entity = $cursor->current();
        $this->assertInstanceOf('stdClass', $entity);
        $this->assertAttributeEquals('B', 'name', $entity);
    }

    public function testShouldGetFirst()
    {
        // Arrange
        $items = [
            ['name' => 'A'],
            ['name' => 'B'],
            ['name' => 'C'],
        ];
        $cursor = $this->getCursor('stdClass', $items);

        $this->setProtected($cursor, 'position', 1);

        // Assert
        $entity = $cursor->first();
        $this->assertInstanceOf('stdClass', $entity);
        $this->assertAttributeEquals('A', 'name', $entity);
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
        $cursor = $this->getCursor('stdClass', $items);

        // Assert
        $this->assertTrue($cursor->valid());
        $this->setProtected($cursor, 'position', 8);
        $this->assertFalse($cursor->valid());
    }

    protected function getCursor(
        $entityClass = 'stdClass',
        $items = []
    ) {
        return new EmbeddedCursor($entityClass, $items);
    }
}
