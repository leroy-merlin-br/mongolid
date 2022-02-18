<?php

namespace Mongolid\Cursor;

use ArrayIterator;
use Iterator;
use IteratorIterator;
use Mockery as m;
use MongoDB\Collection;
use MongoDB\Driver\Exception\LogicException;
use MongoDB\Driver\ReadPreference;
use Mongolid\ActiveRecord;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Container;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\Schema;
use stdClass;
use Mongolid\TestCase;
use Traversable;

class CursorTest extends TestCase
{
    public function tearDown(): void
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
        $this->assertEquals(
            [[], ['limit' => 10]],
            $cursor->params()
        );
    }

    public function testShouldSortDocumentsOfCursor()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->sort(['name' => 1]);
        $this->assertEquals(
            [[], ['sort' => ['name' => 1]]],
            $cursor->params()
        );
    }

    public function testShouldSkipDocuments()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->skip(5);
        $this->assertEquals(
            [[], ['skip' => 5]],
            $cursor->params()
        );
    }

    public function testShouldSetNoCursorTimeoutToTrue()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->disableTimeout();
        $this->assertEquals(
            [[], ['noCursorTimeout' => true]],
            $cursor->params()
        );
    }

    public function testShouldSetReadPreferenceParameterAccordingly()
    {
        // Arrange
        $cursor = $this->getCursor();
        $mode = ReadPreference::RP_SECONDARY;
        $cursor->setReadPreference($mode);
        $readPreferenceParameter = $this->getProtected($cursor, 'params')[1]['readPreference'];

        // Assert
        $this->assertInstanceOf(ReadPreference::class, $readPreferenceParameter);
        $this->assertSame($readPreferenceParameter->getMode(), $mode);
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
        $this->assertEquals(5, $cursor->count());
    }

    public function testShouldCountDocumentsWithCountFunction()
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
        $this->assertEquals(5, count($cursor));
    }

    public function testShouldRewind()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 10);

        // Act
        $driverCursor->shouldReceive('rewind')
            ->once();

        // Assert
        $cursor->rewind();
        $this->assertEquals(0, $cursor->key());
    }

    public function testShouldRewindACursorThatHasAlreadyBeenInitialized()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 10);

        // Act
        $driverCursor->shouldReceive('rewind')
            ->twice()
            ->andReturnUsing(function () use ($cursor) {
                if ($this->getProtected($cursor, 'cursor')) {
                    throw new LogicException('Cursor already initialized', 1);
                }
            });

        // Assert
        $cursor->rewind();
        $this->assertEquals(0, $cursor->key());
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
        $this->assertInstanceOf(stdClass::class, $entity);
        $this->assertEquals('John Doe', $entity->name);
    }

    public function testShouldGetCurrentUsingActiveRecordClasses()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $entity = new class() extends ActiveRecord {
        };
        $entity->name = 'John Doe';
        $driverCursor = new ArrayIterator([$entity]);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Assert
        $entity = $cursor->current();
        $this->assertInstanceOf(ActiveRecord::class, $entity);
        $this->assertEquals('John Doe', $entity->name);
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
        $this->assertInstanceOf(stdClass::class, $entity);
        $this->assertEquals('John Doe', $entity->name);
    }

    public function testShouldGetFirstWhenEmpty()
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
            ->andReturn(null);

        // Assert
        $result = $cursor->first();
        $this->assertNull($result);
    }

    public function testShouldRefreshTheCursor()
    {
        // Arrange
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor();
        $this->setProtected($cursor, 'cursor', $driverCursor);

        // Assert
        $cursor->fresh();
        $this->assertEquals(null, $cursor->key());
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
        $cursor->next();
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
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor(null, $collection, 'find', [['bacon' => true]]);
        $driverCursor = m::mock(Traversable::class);
        $driverIterator = m::mock(Iterator::class);

        // Act
        $collection->shouldReceive('find')
            ->once()
            ->with(['bacon' => true])
            ->andReturn($driverCursor);

        $driverCursor->shouldReceive('getIterator')
            ->andReturn($driverIterator);

        // Because when creating an IteratorIterator with the driverCursor
        // this methods will be called once to initialize the iterable object.
        $driverIterator->shouldReceive('rewind', 'valid', 'current', 'key')
            ->once()
            ->andReturn(true);

        // Assert
        $result = $this->callProtected($cursor, 'getCursor');
        $this->assertInstanceOf(IteratorIterator::class, $result);
    }

    public function testShouldReturnAllResults()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->shouldReceive('rewind', 'valid', 'key')
            ->andReturn(true, true, false);

        $driverCursor->shouldReceive('next')
            ->andReturn(true, false);

        $driverCursor->shouldReceive('current')
            ->twice()
            ->andReturn(
                ['name' => 'bob', 'occupation' => 'coder'],
                ['name' => 'jef', 'occupation' => 'tester']
            );

        $result = $cursor->all();

        // Assert
        $this->assertEquals(
            [
                (object) ['name' => 'bob', 'occupation' => 'coder'],
                (object) ['name' => 'jef', 'occupation' => 'tester'],
            ],
            $result
        );
    }

    public function testShouldReturnResultsToArray()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(null, $collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->shouldReceive('rewind', 'valid', 'key')
            ->andReturn(true, true, false);

        $driverCursor->shouldReceive('next')
            ->andReturn(true, false);

        $driverCursor->shouldReceive('current')
            ->twice()
            ->andReturn(
                ['name' => 'bob', 'occupation' => 'coder'],
                ['name' => 'jef', 'occupation' => 'tester']
            );

        $result = $cursor->toArray();

        // Assert
        $this->assertEquals(
            [
                ['name' => 'bob', 'occupation' => 'coder'],
                ['name' => 'jef', 'occupation' => 'tester'],
            ],
            $result
        );
    }

    public function testShouldSerializeAnActiveCursor()
    {
        // Arrange
        $pool = m::mock(Pool::class);
        $conn = m::mock(Connection::class);
        $schema = new DynamicSchema();
        $cursor = $this->getCursor($schema, null, 'find', [[]]);
        $driverCollection = $this->getDriverCollection();

        $this->setProtected($cursor, 'collection', $driverCollection);

        // Act
        Container::instance(Pool::class, $pool);

        $pool->shouldReceive('getConnection')
            ->andReturn($conn);

        $conn->shouldReceive('getRawConnection')
            ->andReturn($conn);

        $conn->defaultDatabase = 'db';
        $conn->db = $conn;
        $conn->my_collection = $driverCollection; // Return the same driver Collection

        // Assert
        $result = unserialize(serialize($cursor));
        $this->assertEquals($cursor, $result);
    }

    protected function getCursor(
        $entitySchema = null,
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ) {
        if (!$entitySchema) {
            $entitySchema = m::mock(Schema::class.'[]');
        }

        if (!$collection) {
            $collection = m::mock(Collection::class);
        }

        if (!$driverCursor) {
            return new Cursor($entitySchema, $collection, $command, $params);
        }

        $mock = m::mock(
            Cursor::class.'[getCursor]',
            [$entitySchema, $collection, $command, $params]
        );

        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('getCursor')
            ->andReturn($driverCursor);

        $this->setProtected($mock, 'cursor', $driverCursor);

        return $mock;
    }

    /**
     * Since the MongoDB\Collection is not serializable. This method will
     * emulate an unserializable collection from mongoDb driver.
     */
    protected function getDriverCollection()
    {
        /*
         * Emulates a MongoDB\Collection non serializable behavior.
         */
        return new class() implements \Serializable {
            public function serialize()
            {
                throw new Exception('Unable to serialize', 1);
            }

            public function unserialize($serialized)
            {
            }

            public function getCollectionName()
            {
                return 'my_collection';
            }
        };
    }
}
