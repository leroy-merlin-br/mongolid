<?php
namespace Mongolid\Cursor;

use ArrayIterator;
use ArrayObject;
use Exception;
use Iterator;
use Mockery as m;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\LogicException;
use MongoDB\Driver\ReadPreference;
use MongoDB\Model\CachingIterator;
use Mongolid\Connection\Connection;
use Mongolid\Model\AbstractModel;
use Mongolid\TestCase;
use Serializable;
use Traversable;

final class CursorTest extends TestCase
{
    public function testShouldLimitDocumentQuantity(): void
    {
        // Set
        $cursor = $this->getCursor();

        // Actions
        $cursor->limit(10);

        $result = $this->getProtected($cursor, 'params');

        // Assertions
        $this->assertSame([[], ['limit' => 10]], $result);
    }

    public function testShouldSortDocumentsOfCursor(): void
    {
        // Set
        $cursor = $this->getCursor();

        // Actions
        $cursor->sort(['name' => 1]);
        $result = $this->getProtected($cursor, 'params');

        // Assertions
        $this->assertSame([[], ['sort' => ['name' => 1]]], $result);
    }

    public function testShouldSkipDocuments(): void
    {
        // Set
        $cursor = $this->getCursor();

        // Actions
        $cursor->skip(5);
        $result = $this->getProtected($cursor, 'params');

        // Assertions
        $this->assertSame([[], ['skip' => 5]], $result);
    }

    public function testShouldSetNoCursorTimeoutToTrue(): void
    {
        // Set
        $cursor = $this->getCursor();

        // Actions
        $cursor->disableTimeout();
        $result = $this->getProtected($cursor, 'params');

        // Assertions
        $this->assertSame([[], ['noCursorTimeout' => true]], $result);
    }

    public function testShouldSetReadPreferenceParameterAccordingly(): void
    {
        // Set
        $cursor = $this->getCursor();
        $mode = ReadPreference::RP_SECONDARY;

        // Actions
        $cursor->setReadPreference($mode);
        $readPreferenceParameter = $this->getProtected($cursor, 'params')[1]['readPreference'];
        $result = $readPreferenceParameter->getMode();

        // Assertions
        $this->assertInstanceOf(ReadPreference::class, $readPreferenceParameter);
        $this->assertSame($mode, $result);
    }

    public function testShouldBeAbleToSetReadPreferenceAndCursorTimeoutTogether(): void
    {
        // Set
        $cursor = $this->getCursor();
        $mode = ReadPreference::RP_SECONDARY;

        // Actions
        $cursor->setReadPreference($mode);
        $cursor->disableTimeout();
        $readPreferenceParameter = $this->getProtected($cursor, 'params')[1]['readPreference'];
        $result = $readPreferenceParameter->getMode();
        $timeoutResult = $this->getProtected($cursor, 'params')[1]['noCursorTimeout'];

        // Assertions
        $this->assertInstanceOf(ReadPreference::class, $readPreferenceParameter);
        $this->assertSame($mode, $result);
        $this->assertTrue($timeoutResult);
    }

    public function testShouldCountDocuments(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor($collection);

        // Expectations
        $collection
            ->expects('count')
            ->with([])
            ->andReturn(5);

        // Actions
        $result = $cursor->count();

        // Assertions
        $this->assertSame(5, $result);
    }

    public function testShouldCountDocumentsWithCountFunction(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor($collection);

        // Expectations
        $collection->expects()
            ->count([])
            ->andReturn(5);

        // Actions
        $result = count($cursor);

        // Assertions
        $this->assertSame(5, $result);
    }

    public function testShouldRewind(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 10);

        // Expectations
        $driverCursor->expects()
            ->rewind();

        // Actions
        $cursor->rewind();
        $result = $this->getProtected($cursor, 'position');

        // Assertions
        $this->assertSame(0, $result);
    }

    public function testShouldRewindACursorThatHasAlreadyBeenInitialized(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 10);

        // Expectations
        $driverCursor->expects()
            ->rewind()
            ->andReturnUsing(
                function () use ($cursor) {
                    if ($this->getProtected($cursor, 'cursor')) {
                        throw new LogicException('Cursor already initialized', 1);
                    }
                }
            );

        // Actions
        $cursor->rewind();
        $result = $this->getProtected($cursor, 'position');

        // Assertions
        $this->assertSame(0, $result);
    }

    public function testShouldGetCurrent(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $object = new class extends AbstractModel
        {
        };
        $object->name = 'John Doe';
        $driverCursor = new ArrayIterator([$object]);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Actions
        $model = $cursor->current();

        // Assertions
        $this->assertInstanceOf(AbstractModel::class, $model);
        $this->assertSame('John Doe', $model->name);
    }

    public function testShouldGetFirst(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $object = new class extends AbstractModel
        {
        };
        $object->name = 'John Doe';
        $driverCursor = new CachingIterator(new ArrayObject([$object]));
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Actions
        $model = $cursor->first();

        // Assertions
        $this->assertInstanceOf(get_class($object), $model);
        $this->assertSame('John Doe', $model->name);
    }

    public function testShouldGetFirstWhenEmpty(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $driverCursor = new CachingIterator(new ArrayObject());

        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Actions
        $result = $cursor->first();

        // Assertions
        $this->assertNull($result);
    }

    public function testShouldRefreshTheCursor(): void
    {
        // Set
        $driverCursor = new CachingIterator(new ArrayObject());
        $cursor = $this->getCursor();
        $this->setProtected($cursor, 'cursor', $driverCursor);

        // Actions
        $cursor->fresh();
        $result = $this->getProtected($cursor, 'cursor');

        // Assertions
        $this->assertNull($result);
    }

    public function testShouldImplementKeyMethodFromIterator(): void
    {
        // Set
        $cursor = $this->getCursor();
        $this->setProtected($cursor, 'position', 7);

        // Actions
        $result = $cursor->key();

        // Assertions
        $this->assertSame(7, $result);
    }

    public function testShouldImplementNextMethodFromIterator(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        $this->setProtected($cursor, 'position', 7);

        // Expectations
        $driverCursor->expects()
            ->next();

        // Actions
        $cursor->next();

        // Assertions
        $this->assertSame(8, $cursor->key());
    }

    public function testShouldImplementValidMethodFromIterator(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Expectations
        $driverCursor->expects()
            ->valid()
            ->andReturn(true);

        // Actions
        $result = $cursor->valid();

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldWrapMongoDriverCursorWithIterator(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor($collection, 'find', [['bacon' => true]]);
        $driverCursor = m::mock(Traversable::class);
        $driverIterator = m::mock(Iterator::class);

        // Expectations
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

        // Actions
        $result = $this->callProtected($cursor, 'getCursor');

        // Assertions
        $this->assertInstanceOf(CachingIterator::class, $result);
    }

    public function testShouldReturnAllResults(): void
    {
        // Set
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

    public function testShouldReturnResultsToArray(): void
    {
        // Set
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(CachingIterator::class);
        $cursor = $this->getCursor($collection, 'find', [[]], $driverCursor);

        // Expectations
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

        // Actions
        $result = $cursor->toArray();

        // Assertions
        $this->assertSame(
            [
                ['name' => 'bob', 'occupation' => 'coder'],
                ['name' => 'jef', 'occupation' => 'tester'],
            ],
            $result
        );
    }

    public function testShouldSerializeAnActiveCursor(): void
    {
        // Set
        $connection = $this->instance(Connection::class, m::mock(Connection::class));
        $cursor = $this->getCursor(null, 'find', [[]]);
        $driverCollection = $this->getDriverCollection();

        $this->setProtected($cursor, 'collection', $driverCollection);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $connection->defaultDatabase = 'db';

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('db')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('my_collection')
            ->andReturn($driverCollection);

        // Actions
        $result = unserialize(serialize($cursor));

        // Assertions
        $this->assertEquals($cursor, $result);
    }

    protected function getCursor(
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ): Cursor {
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
        return new class() {
            public function __serialize()
            {
                throw new Exception('Unable to serialize', 1);
            }

            public function __unserialize($serialized)
            {
            }

            public function getCollectionName()
            {
                return 'my_collection';
            }
        };
    }
}
