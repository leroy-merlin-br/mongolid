<?php

namespace Mongolid\Cursor;

use ArrayIterator;
use Exception;
use Iterator;
use IteratorIterator;
use Mockery as m;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\Exception\LogicException;
use MongoDB\Driver\ReadPreference;
use Mongolid\Connection\Connection;
use Mongolid\LegacyRecord;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;
use Traversable;

class SchemaCursorTest extends TestCase
{
    public function testShouldLimitDocumentQuantity(): void
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

    public function testShouldSortDocumentsOfCursor(): void
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

    public function testShouldSkipDocuments(): void
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

    public function testShouldSetNoCursorTimeoutToTrue(): void
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

    public function testShouldSetReadPreferenceParameterAccordingly(): void
    {
        // Arrange
        $cursor = $this->getCursor();
        $mode = ReadPreference::RP_SECONDARY;
        $cursor->setReadPreference($mode);
        $readPreferenceParameter = $this->getProtected(
            $cursor,
            'params'
        )[1]['readPreference'];

        // Assert
        $this->assertInstanceOf(
            ReadPreference::class,
            $readPreferenceParameter
        );
        $this->assertSame($readPreferenceParameter->getMode(), $mode);
    }

    public function testShouldCountDocuments(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor(null, $collection);

        // Act
        $collection->shouldReceive('countDocuments')
            ->once()
            ->with([])
            ->andReturn(5);

        // Assert
        $this->assertEquals(5, $cursor->count());
    }

    public function testShouldCountDocumentsWithCountFunction(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor(null, $collection);

        // Act
        $collection->shouldReceive('countDocuments')
            ->once()
            ->with([])
            ->andReturn(5);

        // Assert
        $this->assertEquals(5, count($cursor));
    }

    public function testShouldRewind(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

        $this->setProtected($cursor, 'position', 10);

        // Act
        $driverCursor->shouldReceive('rewind')
            ->once();

        // Assert
        $cursor->rewind();
        $this->assertEquals(0, $cursor->key());
    }

    public function testShouldRewindACursorThatHasAlreadyBeenInitialized(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

        $this->setProtected($cursor, 'position', 10);

        // Act
        $driverCursor->shouldReceive('rewind')
            ->twice()
            ->andReturnUsing(function () use ($cursor): void {
                if ($this->getProtected($cursor, 'cursor')) {
                    throw new LogicException('Cursor already initialized', 1);
                }
            });

        // Assert
        $cursor->rewind();
        $this->assertEquals(0, $cursor->key());
    }

    public function testShouldGetCurrentUsingLegacyRecordClasses(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $entity = new class () extends LegacyRecord {
        };
        $entity->name = 'John Doe';
        $driverCursor = new ArrayIterator([$entity]);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

        // Assert
        $entity = $cursor->current();
        $this->assertInstanceOf(LegacyRecord::class, $entity);
        $this->assertEquals('John Doe', $entity->name);
    }

    public function testShouldGetFirstWhenEmpty(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

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

    public function testShouldRefreshTheCursor(): void
    {
        // Arrange
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor();
        $this->setProtected($cursor, 'cursor', $driverCursor);

        // Assert
        $cursor->fresh();
        $this->assertEquals(null, $cursor->key());
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
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

        $this->setProtected($cursor, 'position', 7);

        // Act
        $driverCursor->shouldReceive('next')
            ->once();

        // Assert
        $cursor->next();
        $this->assertEquals(8, $cursor->key());
    }

    public function testShouldImplementValidMethodFromIterator(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

        // Act
        $driverCursor->shouldReceive('valid')
            ->andReturn(true);

        // Assert
        $this->assertTrue($cursor->valid());
    }

    public function testShouldWrapMongoDriverCursorWithIteratoriterator(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [['bacon' => true]]
        );
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

    public function testShouldReturnResultsToArray(): void
    {
        // Arrange
        $collection = m::mock(Collection::class);
        $driverCursor = m::mock(IteratorIterator::class);
        $cursor = $this->getCursor(
            null,
            $collection,
            'find',
            [[]],
            $driverCursor
        );

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

    public function testShouldSerializeAnActiveCursor(): void
    {
        // Arrange
        $connection = $this->instance(
            Connection::class,
            m::mock(Connection::class)
        );
        $schema = new DynamicSchema();
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $cursor = $this->getCursor($schema, null, 'find', [[]]);

        $driverCollection = $this->getDriverCollection();

        $this->setProtected($cursor, 'collection', $driverCollection);

        // Act
        $connection->shouldReceive('getClient')
            ->andReturn($client);

        $client->shouldReceive('selectDatabase')
            ->with('db', ['document' => 'array'])
            ->andReturn($database);

        $database->shouldReceive('selectCollection')
            ->andReturn($driverCollection);

        $connection->defaultDatabase = 'db';
        $connection->db = $connection;
        $connection->my_collection = $driverCollection; // Return the same driver Collection

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
            $entitySchema = m::mock(Schema::class . '[]');
        }

        if (!$collection) {
            $collection = m::mock(Collection::class);
        }

        if (!$driverCursor) {
            return new SchemaCursor(
                $entitySchema,
                $collection,
                $command,
                $params
            );
        }

        $mock = m::mock(
            SchemaCursor::class . '[getCursor]',
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
    protected function getDriverCollection(): Collection
    {
        /*
         * Emulates a MongoDB\Collection non serializable behavior.
         */
        return new class () extends Collection {
            public function __construct()
            {
            }

            public function getCollectionName(): string
            {
                return 'my_collection';
            }

            public function __serialize(): array
            {
                throw new Exception('Unable to serialize', 1);
            }
        };
    }
}
