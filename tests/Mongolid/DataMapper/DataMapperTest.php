<?php

namespace Mongolid\DataMapper;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CacheableCursor;
use Mongolid\Cursor\Cursor;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Event\EventTriggerService;
use Mongolid\Schema;
use TestCase;
use stdClass;

class DataMapperTest extends TestCase
{
    public function tearDown()
    {
        $this->eventService = null;
        parent::tearDown();
        m::close();
    }

    public function testShouldBeAbleToConstructWithSchema()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper = new DataMapper($connPool);

        // Assertion
        $this->assertAttributeEquals($connPool, 'connPool', $mapper);
    }

    public function testShouldSave()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock(Collection::class);
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('updateOne')
            ->once()
            ->with(
                ['_id' => 123],
                ['$set' => $parsedObject],
                ['upsert' => true]
            )->andReturn($operationResult);

        $operationResult->shouldReceive('getModifiedCount', 'getUpsertedCount')
            ->once()
            ->andReturn(1);

        $this->expectEvent('saving', $object, true);
        $this->expectEvent('saved', $object, false);

        // Assert
        $this->assertTrue($mapper->save($object));
    }

    public function testShouldInsert()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock(Collection::class);
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('insertOne')
            ->once()
            ->with($parsedObject)
            ->andReturn($operationResult);

        $operationResult->shouldReceive('getInsertedCount')
            ->once()
            ->andReturn(1);

        $this->expectEvent('inserting', $object, true);
        $this->expectEvent('inserted', $object, false);

        // Assert
        $this->assertTrue($mapper->insert($object));
    }

    public function testShouldUpdate()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock(Collection::class);
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('updateOne')
            ->once()
            ->with(
                ['_id' => 123],
                ['$set' => $parsedObject]
            )->andReturn($operationResult);

        $operationResult->shouldReceive('getModifiedCount')
            ->once()
            ->andReturn(1);

        $this->expectEvent('updating', $object, true);
        $this->expectEvent('updated', $object, false);

        // Assert
        $this->assertTrue($mapper->update($object));
    }

    public function testShouldDelete()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connPool]);

        $collection      = m::mock(Collection::class);
        $object          = m::mock();
        $parsedObject    = ['_id' => 123];
        $operationResult = m::mock();

        $object->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->once()
            ->with($object)
            ->andReturn($parsedObject);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('deleteOne')
            ->once()
            ->with(['_id' => 123])
            ->andReturn($operationResult);

        $operationResult->shouldReceive('getDeletedCount')
            ->once()
            ->andReturn(1);

        $this->expectEvent('deleting', $object, true);
        $this->expectEvent('deleted', $object, false);

        // Assert
        $this->assertTrue($mapper->delete($object));
    }

    /**
     * @dataProvider eventsToBailOperations
     */
    public function testDatabaseOperationsShouldBailOutIfTheEventHandlerReturnsFalse($operation, $dbOperation, $eventName)
    {
        // Arrange
        $connPool   = m::mock(Pool::class);
        $mapper     = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connPool]);
        $collection = m::mock(Collection::class);
        $object     = m::mock();

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('parseToDocument')
            ->with($object)
            ->never();

        $mapper->shouldReceive('getCollection')
            ->andReturn($collection);

        $collection->shouldReceive($dbOperation)
            ->never();

        /* "Mocks" the fireEvent to return false and bail the operation */
        $this->expectEvent($eventName, $object, true, false);

        // Assert
        $this->assertFalse($mapper->$operation($object));
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[prepareValueQuery,getCollection]', [$connPool]);
        $schema   = m::mock(Schema::class);

        $collection      = m::mock(Collection::class);
        $query           = 123;
        $preparedQuery   = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('prepareValueQuery')
            ->with($query)
            ->andReturn($preparedQuery);

        $mapper->shouldReceive('getCollection')
            ->andReturn($collection);

        // Assert
        $result = $mapper->where($query);

        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertNotInstanceOf(CacheableCursor::class, $result);
        $this->assertAttributeEquals($schema, 'entitySchema', $result);
        $this->assertAttributeEquals($collection, 'collection', $result);
        $this->assertAttributeEquals('find', 'command', $result);
        $this->assertAttributeEquals([$preparedQuery], 'params', $result);

        $cacheableResult = $mapper->where($query, true);
        $this->assertInstanceOf(CacheableCursor::class, $cacheableResult);
        $this->assertAttributeEquals($schema, 'entitySchema', $cacheableResult);
        $this->assertAttributeEquals($collection, 'collection', $cacheableResult);
    }

    public function testShouldGetAll()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper         = m::mock(DataMapper::class.'[where]', [$connPool]);
        $mongolidCursor = m::mock('Mongolid\Cursor\Cursor');

        // Act
        $mapper->shouldReceive('where')
            ->once()
            ->with([])
            ->andReturn($mongolidCursor);

        // Assert
        $this->assertEquals(
            $mongolidCursor,
            $mapper->all()
        );
    }

    public function testShouldGetFirstWithQuery()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[prepareValueQuery,getCollection]', [$connPool]);
        $schema   = m::mock(Schema::class);

        $collection      = m::mock(Collection::class);
        $query           = 123;
        $preparedQuery   = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('prepareValueQuery')
            ->once()
            ->with($query)
            ->andReturn($preparedQuery);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('findOne')
            ->once()
            ->with($preparedQuery)
            ->andReturn(['name' => 'John Doe']);

        // Assert
        $result = $mapper->first($query);

        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertAttributeEquals('John Doe', 'name', $result);
    }

    public function testShouldGetNullIfFirstCantFindAnything()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[prepareValueQuery,getCollection]', [$connPool]);
        $schema   = m::mock(Schema::class);

        $collection      = m::mock(Collection::class);
        $query           = 123;
        $preparedQuery   = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->schema = $schema;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('prepareValueQuery')
            ->once()
            ->with($query)
            ->andReturn($preparedQuery);

        $mapper->shouldReceive('getCollection')
            ->once()
            ->andReturn($collection);

        $collection->shouldReceive('findOne')
            ->once()
            ->with($preparedQuery)
            ->andReturn(null);

        // Assert
        $result = $mapper->first($query);

        $this->assertNull($result);
    }

    public function testShouldGetFirstTroughACacheableCursor()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper   = m::mock(DataMapper::class.'[where]', [$connPool]);
        $query    = 123;
        $entity   = new stdClass;
        $cursor   = m::mock(CacheableCursor::class);

        // Act
        $mapper->shouldReceive('where')
            ->once()
            ->with($query, true)
            ->andReturn($cursor);

        $cursor->shouldReceive('first')
            ->once()
            ->andReturn($entity);

        // Assert
        $this->assertEquals(
            $entity,
            $mapper->first($query, true)
        );
    }

    public function testShouldParseObjectToDocumentAndPutResultingIdIntoTheGivenObject()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper = m::mock(DataMapper::class.'[getSchemaMapper]', [$connPool]);
        $object         = m::mock();
        $parsedDocument = ['a_field' => 123, '_id' => 'bacon'];
        $schemaMapper   = m::mock('Mongolid\Schema[]');

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->shouldReceive('getSchemaMapper')
            ->once()
            ->andReturn($schemaMapper);

        $schemaMapper->shouldReceive('map')
            ->once()
            ->with($object)
            ->andReturn($parsedDocument);

        // Assert
        $this->assertEquals(
            $parsedDocument,
            $this->callProtected($mapper, 'parseToDocument', $object)
        );

        $this->assertEquals(
            'bacon', // Since this was the parsedDocument _id
            $object->_id
        );
    }

    public function testShouldGetSchemaMapper()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper = new DataMapper($connPool);
        $mapper->schemaClass = 'MySchema';
        $schema = m::mock('Mongolid\Schema');

        // Act
        Ioc::instance('MySchema', $schema);

        // Assert
        $result = $this->callProtected($mapper, 'getSchemaMapper');
        $this->assertInstanceOf('Mongolid\DataMapper\SchemaMapper', $result);
        $this->assertEquals($schema, $result->schema);
    }

    public function testShouldGetRawCollection()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper = new DataMapper($connPool);
        $connection = m::mock(Connection::class);
        $collection = m::mock(Collection::class);

        $mapper->schema = (object) ['collection' => 'foobar'];
        $connection->defaultDatabase = 'grimory';
        $connection->grimory = (object) ['foobar' => $collection];

        // Act
        $connPool->shouldReceive('getConnection')
            ->once()
            ->andReturn($connection);

        $connection->shouldReceive('getRawConnection')
            ->andReturn($connection);

        // Assertion
        $this->assertEquals(
            $collection,
            $this->callProtected($mapper, 'getCollection')
        );
    }

    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue($value, $expectation)
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $mapper = new DataMapper($connPool);

        $result = $this->callProtected($mapper, 'prepareValueQuery', [$value]);

        // Assertion
        $this->assertEquals(
            $expectation,
            $this->callProtected($mapper, 'prepareValueQuery', [$value])
        );
    }

    protected function expectEvent($event, $entity, bool $halt, $return = true)
    {
        if (! ($this->eventService ?? false)) {
            $this->eventService = m::mock(EventTriggerService::class);
            Ioc::instance(EventTriggerService::class, $this->eventService);
        }

        $event = 'mongolid.'.$event.'.'.get_class($entity);

        $this->eventService->shouldReceive('fire')
            ->with($event, $entity, $halt)
            ->atLeast()->once()
            ->andReturn($return);
    }

    public function eventsToBailOperations()
    {
        return [
            'Saving event' => [
                'operation' => 'save',
                'dbOperation' => 'updateOne',
                'eventName' => 'saving'
            ],
            // ------------------------
            'Inserting event' => [
                'operation' => 'insert',
                'dbOperation' => 'insertOne',
                'eventName' => 'inserting'
            ],
            // ------------------------
            'Updating event' => [
                'operation' => 'update',
                'dbOperation' => 'updateOne',
                'eventName' => 'updating'
            ],
            // ------------------------
            'Deleting event' => [
                'operation' => 'delete',
                'dbOperation' => 'deleteOne',
                'eventName' => 'deleting'
            ],
        ];
    }

    public function queryValueScenarios()
    {
        return [
            'An array' => [
                'value' => ['age' => ['$gt' => 25]],
                'expectation' => ['age' => ['$gt' => 25]],
            ],
            // ------------------------
            'An ObjectId string' => [
                'value' => '507f1f77bcf86cd799439011',
                'expectation' => ['_id' => new ObjectID('507f1f77bcf86cd799439011')],
            ],
            // ------------------------
            'Other type of _id, sequence for example' => [
                'value' => 7,
                'expectation' => ['_id' => 7],
            ],
        ];
    }
}
