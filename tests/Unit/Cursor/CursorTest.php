<?php
namespace Mongolid\Cursor;

use ArrayIterator;
use ArrayObject;
use Iterator;
use Mockery as m;
use MongoDB\Collection;
use MongoDB\Driver\Exception\LogicException;
use MongoDB\Driver\ReadPreference;
use MongoDB\Model\CachingIterator;
use Mongolid\Connection\Connection;
use Mongolid\Model\AbstractModel;
use Mongolid\TestCase;
use Serializable;
use Traversable;

class CursorTest extends TestCase
{
    public function testShouldLimitDocumentQuantity()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->limit(10);
        $this->assertAttributeEquals(
            [[], ['limit' => 10]],
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
            [[], ['sort' => ['name' => 1]]],
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
            [[], ['skip' => 5]],
            'params',
            $cursor
        );
    }

    public function testShouldSetNoCursorTimeoutToTrue()
    {
        // Arrange
        $cursor = $this->getCursor();

        // Assert
        $cursor->disableTimeout();
        $this->assertAttributeEquals(
            [[], ['noCursorTimeout' => true]],
            'params',
            $cursor
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
        $cursor = $this->getCursor($collection);

        // Act
        $collection->expects()
            ->count([])
            ->andReturn(5);

        // Assert
        $this->assertEquals(5, $cursor->count());
    }

    public function testShouldCountDocumentsWithCountFunction()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor($collection);

        // Act
        $collection->expects()
            ->count([])
            ->andReturn(5);

        // Assert
        $this->assertEquals(5, count($cursor));
    }

    public function testShouldRewind()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 10);

        // Act
        $driverCursor->expects()
            ->rewind();

        // Assert
        $cursor->rewind();
        $this->assertAttributeEquals(0, 'position', $cursor);
    }

    public function testShouldRewindACursorThatHasAlreadyBeenInitialized()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 10);

        // Act
        $driverCursor->expects()
            ->rewind()
            ->andReturnUsing(
                function () use ($cursor) {
                    if ($this->getProtected($cursor, 'cursor')) {
                        throw new LogicException('Cursor already initialized', 1);
                    }
                }
            );

        // Assert
        $cursor->rewind();
        $this->assertAttributeEquals(0, 'position', $cursor);
    }

    public function testShouldGetCurrent()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $object = new class extends AbstractModel
        {
        };
        $object->name = 'John Doe';
        $driverCursor = new ArrayIterator([$object]);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Assert
        $model = $cursor->current();
        $this->assertInstanceOf(AbstractModel::class, $model);
        $this->assertEquals('John Doe', $model->name);
    }

    public function testShouldGetFirst()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $object = new class extends AbstractModel
        {
        };
        $object->name = 'John Doe';
        $driverCursor = new CachingIterator(new ArrayObject([$object]));
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Act
        $model = $cursor->first();

        // Assert
        $this->assertInstanceOf(get_class($object), $model);
        $this->assertSame('John Doe', $model->name);
    }

    public function testShouldGetFirstWhenEmpty()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = new CachingIterator(new ArrayObject());

        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Act
        $result = $cursor->first();

        // Assert
        $this->assertNull($result);
    }

    public function testShouldRefreshTheCursor()
    {
        // Arrange
        $driverCursor = new CachingIterator(new ArrayObject());
        $cursor = $this->getCursor();
        $this->setProtected($cursor, 'cursor', $driverCursor);

        // Assert
        $cursor->fresh();
        $this->assertAttributeEquals(null, 'cursor', $cursor);
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
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 7);

        // Act
        $driverCursor->expects()
            ->next();

        // Assert
        $cursor->next();
        $this->assertEquals(8, $cursor->key());
    }

    public function testShouldImplementValidMethodFromIterator()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->expects()
            ->valid()
            ->andReturn(true);

        // Assert
        $this->assertTrue($cursor->valid());
    }

    public function testShouldWrapMongoDriverCursorWithIterator()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor($collection, 'find', [['bacon' => true]]);
        $driverCursor = m::mock(Traversable::class);
        $driverIterator = m::mock(Iterator::class);

        // Act
        $collection->expects()
            ->find(['bacon' => true])
            ->andReturn($driverCursor);

        $driverCursor->expects()
            ->getIterator()
            ->andReturn($driverIterator);

        // Because when creating an IteratorIterator with the driverCursor
        // this methods will be called once to initialize the iterable object.
        $driverIterator->expects()
            ->rewind()
            ->andReturn(true);

        $driverIterator->expects()
            ->valid()
            ->andReturn(true);

        $driverIterator->expects()
            ->current()
            ->andReturn(true);

        $driverIterator->expects()
            ->key()
            ->andReturn(true);

        // Assert
        $result = $this->callProtected($cursor, 'getCursor');
        $this->assertInstanceOf(CachingIterator::class, $result);
    }

    public function testShouldReturnAllResults()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $object = new class extends AbstractModel
        {
        };
        $class = get_class($object);
        $bob = new $class();
        $bob->name = 'bob';
        $bob->occupation = 'coder';

        $jef = new $class();
        $jef->name = 'jef';
        $jef->occupation = 'tester';

        $driverCursor = new CachingIterator(new ArrayObject([$bob, $jef]));
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Actions
        $result = $cursor->all();

        // Assertions
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf($class, $result);

        $firstModel = $result[0];
        $this->assertSame('bob', $firstModel->name);
        $this->assertSame('coder', $firstModel->occupation);

        $nextModel = $result[1];
        $this->assertSame('jef', $nextModel->name);
        $this->assertSame('tester', $nextModel->occupation);
    }

    public function testShouldReturnResultsToArray()
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Act
        $driverCursor->expects()
            ->rewind();

        $driverCursor->expects()
            ->valid()
            ->times(3)
            ->andReturn(true, true, false);

        $driverCursor->expects()
            ->next()
            ->twice()
            ->andReturn(true, false);

        $driverCursor->expects()
            ->current()
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
        $connection = $this->instance(Connection::class, m::mock(Connection::class));
        $cursor = $this->getCursor(null, 'find', [[]]);
        $driverCollection = $this->getDriverCollection();

        $this->setProtected($cursor, 'collection', $driverCollection);

        // Act
        $connection->expects()
            ->getRawConnection()
            ->andReturn($connection);

        $connection->defaultDatabase = 'db';
        $connection->db = $connection;
        $connection->my_collection = $driverCollection; // Return the same driver Collection

        // Assert
        $result = unserialize(serialize($cursor));
        $this->assertEquals($cursor, $result);
    }

    protected function getCursor(
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ) {
        if (!$collection) {
            $collection = m::mock(Collection::class);
        }

        if (!$driverCursor) {
            return new Cursor($collection, $command, $params);
        }

        $mock = m::mock(
            Cursor::class.'[getCursor]',
            [$collection, $command, $params]
        );

        $mock->shouldAllowMockingProtectedMethods();
        $mock->allows()
            ->getCursor()
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
        return new class() implements Serializable
        {
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
