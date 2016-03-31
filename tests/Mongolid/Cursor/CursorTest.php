<?php

namespace Mongolid\Cursor;

use Mockery as m;
use TestCase;
use IteratorIterator;
use MongoDB\Collection;
use MongoDB\Driver\Cursor as DriverCursor;

class CursorTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldLimitDocumentQuantity()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->limit(10);
        $this->assertAttributeEquals(
            [[],['limit' => 10]],
            'params',
            $cursor
        );
    }

    public function testShouldSortDocumentsOfCursor()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->sort(['name' => 1]);
        $this->assertAttributeEquals(
            [[],['sort' => ['name' => 1]]],
            'params',
            $cursor
        );
    }

    public function testShouldSkipDocuments()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->skip(5);
        $this->assertAttributeEquals(
            [[],['skip' => 5]],
            'params',
            $cursor
        );
    }

    public function testShouldCountDocuments()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor(null, $collection);

        // Act
        $collection->shouldReceive('count')
            ->once()
            ->with([])
            ->andReturn(5);

        // Assert
        $this->assertEquals(5, $cursor->count(5));
    }

    public function testShouldRewind()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->shouldReceive('rewind')
            ->once();

        // Assert
        $cursor->rewind();
        $this->assertAttributeEquals(0, 'position', $cursor);
    }

    public function testShouldGetCurrent()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->shouldReceive('current')
            ->once()
            ->andReturn(['name' => 'John Doe']);

        // Assert
        $entity = $cursor->current();
        $this->assertInstanceOf('stdClass', $entity);
        $this->assertAttributeEquals('John Doe', 'name', $entity);
    }

    public function testShouldGetFirst()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->shouldReceive('rewind')
            ->once();

        $driverCursor->shouldReceive('current')
            ->once()
            ->andReturn(['name' => 'John Doe']);

        // Assert
        $entity = $cursor->first();
        $this->assertInstanceOf('stdClass', $entity);
        $this->assertAttributeEquals('John Doe', 'name', $entity);
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
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 7);

        // Act
        $driverCursor->shouldReceive('next')
            ->once();

        // Assert
        $entity = $cursor->next();
        $this->assertEquals(8, $cursor->key());
    }

    public function testShouldImplementValidMethodFromIterator()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->shouldReceive('valid')
            ->andReturn(true);

        // Assert
        $this->assertTrue($cursor->valid());
    }

    public function testShouldWrapMongoDriverCursorWithIteratoriterator()
    {
        // Arrange
        $collection     = m::mock(Collection::class);
        $cursor         = $this->getCursor(null, $collection, 'find', ['bacon' => true]);
        $driverCursor   = m::mock('Traversable');
        $driverIterator = m::mock('Iterator');

        // Act
        $collection->shouldReceive('find')
            ->once()
            ->with(['bacon' => true])
            ->andReturn($driverCursor);

        $driverCursor->shouldReceive('getIterator')
            ->andReturn($driverIterator);

        // Because when creating an IteratorIterator with the driverCursor
        // this methods will be called once to initialize the iterable object.
        $driverIterator->shouldReceive('rewind','valid','current','key')
            ->once()
            ->andReturn(true);

        // Assert
        $result = $this->callProtected($cursor, 'getCursor');
        $this->assertInstanceOf(IteratorIterator::class, $result);
    }

    protected function getCursor(
        $entityClass = null,
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ) {
        if (! $entityClass) {
            $entityClass = 'stdClass';
        }

        if (! $collection) {
            $collection = m::mock(Collection::class);
        }

        if (! $driverCursor) {
            return new Cursor($entityClass, $collection, $command, $params);
        }

        $mock = m::mock(
            Cursor::class.'[getCursor]',
            [$entityClass, $collection, $command, $params]
        );

        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('getCursor')
            ->andReturn($driverCursor);

        return $mock;
    }
}
