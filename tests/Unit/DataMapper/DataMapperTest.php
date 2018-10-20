<?php
namespace Mongolid\DataMapper;

use InvalidArgumentException;
use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CacheableCursor;
use Mongolid\Cursor\Cursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\AttributesAccessInterface;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;
use stdClass;

class DataMapperTest extends TestCase
{
    public function testShouldBeAbleToConstructWithSchema()
    {
        // Arrange
        $connection = m::mock(Connection::class);

        // Act
        $mapper = new DataMapper($connection);

        // Assert
        $this->assertAttributeEquals($connection, 'connection', $mapper);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldSave($entity, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();

        $entity->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->expects()
            ->parseToDocument($entity)
            ->andReturn($parsedObject);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->replaceOne(
                ['_id' => 123],
                $parsedObject,
                ['upsert' => true, 'writeConcern' => new WriteConcern($writeConcern)]
            )->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getModifiedCount()
            ->andReturn(1);

        $operationResult->allows()
            ->getUpsertedCount()
            ->andReturn(1);

        if ($entity instanceof AttributesAccessInterface) {
            $entity->expects()
                ->syncOriginalAttributes();
        }

        $this->expectEventToBeFired('saving', $entity, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('saved', $entity, false);
        } else {
            $this->expectEventNotToBeFired('saved', $entity);
        }

        // Assert
        $this->assertEquals($expected, $mapper->save($entity, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldInsert($entity, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();

        $entity->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->expects()
            ->parseToDocument($entity)
            ->andReturn($parsedObject);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->insertOne($parsedObject, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getInsertedCount()
            ->andReturn(1);

        if ($entity instanceof AttributesAccessInterface) {
            $entity->expects()
                ->syncOriginalAttributes();
        }

        $this->expectEventToBeFired('inserting', $entity, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('inserted', $entity, false);
        } else {
            $this->expectEventNotToBeFired('inserted', $entity);
        }

        // Assert
        $this->assertEquals($expected, $mapper->insert($entity, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldInsertWithoutFiringEvents($entity, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();

        $entity->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->expects()
            ->parseToDocument($entity)
            ->andReturn($parsedObject);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->insertOne($parsedObject, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getInsertedCount()
            ->andReturn(1);

        if ($entity instanceof AttributesAccessInterface) {
            $entity->expects()
                ->syncOriginalAttributes();
        }

        $this->expectEventNotToBeFired('inserting', $entity);
        $this->expectEventNotToBeFired('inserted', $entity);

        // Assert
        $this->assertEquals($expected, $mapper->insert($entity, $options, false));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldUpdate($entity, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $entity->_id = 123;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->expects()
            ->parseToDocument($entity)
            ->andReturn($parsedObject);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->updateOne(
                ['_id' => 123],
                ['$set' => $parsedObject],
                ['writeConcern' => new WriteConcern($writeConcern)]
            )->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getModifiedCount()
            ->andReturn(1);

        if ($entity instanceof AttributesAccessInterface) {
            $entity->expects()
                ->syncOriginalAttributes();
        }

        $this->expectEventToBeFired('updating', $entity, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('updated', $entity, false);
        } else {
            $this->expectEventNotToBeFired('updated', $entity);
        }

        // Assert
        $this->assertEquals($expected, $mapper->update($entity, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testUpdateShouldCallInsertWhenObjectHasNoId(
        $entity,
        $writeConcern,
        $shouldFireEventAfter,
        $expected
    ) {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $entity->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->expects()
            ->parseToDocument($entity)
            ->andReturn($parsedObject);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->insertOne(
                $parsedObject,
                ['writeConcern' => new WriteConcern($writeConcern)]
            )->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getInsertedCount()
            ->andReturn(1);

        if ($entity instanceof AttributesAccessInterface) {
            $entity->expects()
                ->syncOriginalAttributes();
        }

        $this->expectEventToBeFired('updating', $entity, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('updated', $entity, false);
        } else {
            $this->expectEventNotToBeFired('updated', $entity);
        }

        $this->expectEventNotToBeFired('inserting', $entity);
        $this->expectEventNotToBeFired('inserted', $entity);

        // Assert
        $this->assertEquals($expected, $mapper->update($entity, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldDelete($entity, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $entity->_id = null;

        // Act
        $mapper->shouldAllowMockingProtectedMethods();

        $mapper->expects()
            ->parseToDocument($entity)
            ->andReturn($parsedObject);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->deleteOne(['_id' => 123], ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getDeletedCount()
            ->andReturn(1);

        if ($entity instanceof AttributesAccessInterface) {
            $entity->expects()
                ->syncOriginalAttributes();
        }

        $this->expectEventToBeFired('deleting', $entity, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('deleted', $entity, false);
        } else {
            $this->expectEventNotToBeFired('deleted', $entity);
        }

        // Assert
        $this->assertEquals($expected, $mapper->delete($entity, $options));
    }

    /**
     * @dataProvider eventsToBailOperations
     */
    public function testDatabaseOperationsShouldBailOutIfTheEventHandlerReturnsFalse(
        $operation,
        $dbOperation,
        $eventName
    ) {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[parseToDocument,getCollection]', [$connection]);
        $collection = m::mock(Collection::class);
        $entity = m::mock();

        $mapper->shouldAllowMockingProtectedMethods();

        // Expect
        $mapper->expects()
            ->parseToDocument($entity)
            ->never();

        $mapper->allows()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects($dbOperation)
            ->never();

        /* "Mocks" the fireEvent to return false and bail the operation */
        $this->expectEventToBeFired($eventName, $entity, true, false);

        // Act
        $result = $mapper->$operation($entity);

        // Assert
        $this->assertFalse($result);
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[prepareValueQuery,getCollection]', [$connection]);
        $schema = m::mock(Schema::class);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $projection = ['project' => true, '_id' => false];

        $schema->entityClass = 'stdClass';
        $mapper->setSchema($schema);

        $mapper->shouldAllowMockingProtectedMethods();

        // Expect
        $mapper->expects()
            ->prepareValueQuery($query)
            ->twice()
            ->andReturn($preparedQuery);

        $mapper->expects()
            ->getCollection()
            ->twice()
            ->andReturn($collection);

        // Act
        $result = $mapper->where($query, $projection);
        $cacheableResult = $mapper->where($query, [], true);

        // Assert
        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertNotInstanceOf(CacheableCursor::class, $result);
        $this->assertAttributeEquals($schema, 'entitySchema', $result);
        $this->assertAttributeEquals($collection, 'collection', $result);
        $this->assertAttributeEquals('find', 'command', $result);
        $this->assertAttributeEquals(
            [$preparedQuery, ['projection' => $projection]],
            'params',
            $result
        );

        $this->assertInstanceOf(CacheableCursor::class, $cacheableResult);
        $this->assertAttributeEquals($schema, 'entitySchema', $cacheableResult);
        $this->assertAttributeEquals($collection, 'collection', $cacheableResult);
        $this->assertAttributeEquals(
            [$preparedQuery, ['projection' => []]],
            'params',
            $cacheableResult
        );
    }

    public function testShouldGetAll()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[where]', [$connection]);
        $mongolidCursor = m::mock(Cursor::class);

        // Expect
        $mapper->expects()
            ->where([])
            ->andReturn($mongolidCursor);

        // Act
        $result = $mapper->all();

        // Assert
        $this->assertEquals($mongolidCursor, $result);
    }

    public function testShouldGetFirstWithQuery()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[prepareValueQuery,getCollection]', [$connection]);
        $schema = m::mock(Schema::class);
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->setSchema($schema);

        $mapper->shouldAllowMockingProtectedMethods();

        // Act
        $mapper->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn(['name' => 'John Doe']);

        $result = $mapper->first($query);

        // Assert
        $this->assertInstanceOf(stdClass::class, $result);
        $this->assertAttributeEquals('John Doe', 'name', $result);
    }

    public function testShouldGetNullIfFirstCantFindAnything()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[prepareValueQuery,getCollection]', [$connection]);
        $schema = m::mock(Schema::class);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];

        $schema->entityClass = 'stdClass';
        $mapper->setSchema($schema);

        $mapper->shouldAllowMockingProtectedMethods();

        // Expect
        $mapper->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn(null);

        // Act
        $result = $mapper->first($query);

        // Assert
        $this->assertNull($result);
    }

    public function testShouldGetFirstProjectingFields()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(
            DataMapper::class.'[prepareValueQuery,getCollection]',
            [$connection]
        );
        $schema = m::mock(Schema::class);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $projection = ['project' => true, 'fields' => false];

        $schema->entityClass = 'stdClass';
        $mapper->setSchema($schema);

        $mapper->shouldAllowMockingProtectedMethods();

        // Expect
        $mapper->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $mapper->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => $projection])
            ->andReturn(null);

        // Act
        $result = $mapper->first($query, $projection);

        // Assert
        $this->assertNull($result);
    }

    public function testShouldGetFirstTroughACacheableCursor()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[where]', [$connection]);
        $query = 123;
        $entity = new stdClass();
        $cursor = m::mock(CacheableCursor::class);

        // Expect
        $mapper->expects()
            ->where($query, [], true)
            ->andReturn($cursor);

        $cursor->expects()
            ->first()
            ->andReturn($entity);

        // Act
        $result = $mapper->first($query, [], true);

        // Assert
        $this->assertEquals($entity, $result);
    }

    public function testShouldGetFirstTroughACacheableCursorProjectingFields()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[where]', [$connection]);
        $query = 123;
        $entity = new stdClass();
        $cursor = m::mock(CacheableCursor::class);
        $projection = ['project' => true, '_id' => false];

        // Expect
        $mapper->expects()
            ->where($query, $projection, true)
            ->andReturn($cursor);

        $cursor->expects()
            ->first()
            ->andReturn($entity);

        // Act
        $result = $mapper->first($query, $projection, true);

        // Assert
        $this->assertEquals($entity, $result);
    }

    public function testShouldParseObjectToDocumentAndPutResultingIdIntoTheGivenObject()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = m::mock(DataMapper::class.'[getSchemaMapper]', [$connection]);
        $entity = m::mock();
        $parsedDocument = ['a_field' => 123, '_id' => 'bacon'];
        $schemaMapper = m::mock(Schema::class.'[]');

        $mapper->shouldAllowMockingProtectedMethods();

        // Expect
        $mapper->expects()
            ->getSchemaMapper()
            ->andReturn($schemaMapper);

        $schemaMapper->expects()
            ->map($entity)
            ->andReturn($parsedDocument);

        // Act
        $result = $this->callProtected($mapper, 'parseToDocument', $entity);

        // Assert
        $this->assertEquals($parsedDocument, $result);
        $this->assertEquals(
            'bacon', // Since this was the parsedDocument _id
            $entity->_id
        );
    }

    public function testShouldGetSchemaMapper()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = new DataMapper($connection);
        $mapper->schemaClass = 'MySchema';
        $schema = m::mock(Schema::class);

        Ioc::instance('MySchema', $schema);

        // Act
        $result = $this->callProtected($mapper, 'getSchemaMapper');

        // Assert
        $this->assertInstanceOf(SchemaMapper::class, $result);
        $this->assertEquals($schema, $result->schema);
    }

    public function testShouldGetRawCollection()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = new DataMapper($connection);
        $collection = m::mock(Collection::class);
        $schema = m::mock(Schema::class);
        $schema->collection = 'foobar';

        $mapper->setSchema($schema);
        $connection->defaultDatabase = 'grimory';
        $connection->grimory = (object) ['foobar' => $collection];

        // Expect
        $connection->expects()
            ->getRawConnection()
            ->andReturn($connection);

        // Act
        $result = $this->callProtected($mapper, 'getCollection');

        // Assert
        $this->assertEquals($collection, $result);
    }

    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue($value, $expectation)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = new DataMapper($connection);

        // Act
        $result = $this->callProtected($mapper, 'prepareValueQuery', [$value]);

        // Assert
        $this->assertMongoQueryEquals($expectation, $result);
    }

    /**
     * @dataProvider getProjections
     */
    public function testPrepareProjectionShouldConvertArray($data, $expectation)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = new DataMapper($connection);

        // Act
        $result = $this->callProtected($mapper, 'prepareProjection', [$data]);

        // Assert
        $this->assertEquals($expectation, $result);
    }

    public function testPrepareProjectionShouldThrownAnException()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $mapper = new DataMapper($connection);
        $data = ['valid' => true, 'invalid-key' => 'invalid-value'];

        // Expectations
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid projection: 'invalid-key' => 'invalid-value'");

        // Act
        $this->callProtected($mapper, 'prepareProjection', [$data]);
    }

    protected function getEventService()
    {
        if (!Ioc::has(EventTriggerService::class)) {
            Ioc::instance(EventTriggerService::class, m::mock(EventTriggerService::class));
        }

        return Ioc::make(EventTriggerService::class);
    }

    protected function expectEventToBeFired($event, $entity, bool $halt, $return = true)
    {
        $event = 'mongolid.'.$event.': '.get_class($entity);

        $this->getEventService()->expects()
            ->fire($event, $entity, $halt)
            ->andReturn($return);
    }

    protected function expectEventNotToBeFired($event, $entity)
    {
        $event = 'mongolid.'.$event.': '.get_class($entity);

        $this->getEventService()->expects()
            ->fire($event, $entity, m::any())
            ->never();
    }

    public function eventsToBailOperations()
    {
        return [
            'Saving event' => [
                'operation' => 'save',
                'dbOperation' => 'replaceOne',
                'eventName' => 'saving',
            ],
            // ------------------------
            'Inserting event' => [
                'operation' => 'insert',
                'dbOperation' => 'insertOne',
                'eventName' => 'inserting',
            ],
            // ------------------------
            'Updating event' => [
                'operation' => 'update',
                'dbOperation' => 'updateOne',
                'eventName' => 'updating',
            ],
            // ------------------------
            'Deleting event' => [
                'operation' => 'delete',
                'dbOperation' => 'deleteOne',
                'eventName' => 'deleting',
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
            'An ObjectId string within a query' => [
                'value' => ['_id' => '507f1f77bcf86cd799439011'],
                'expectation' => ['_id' => new ObjectID('507f1f77bcf86cd799439011')],
            ],
            // ------------------------
            'Other type of _id, sequence for example' => [
                'value' => 7,
                'expectation' => ['_id' => 7],
            ],
            // ------------------------
            'Series of string _ids as the $in parameter' => [
                'value' => ['_id' => ['$in' => ['507f1f77bcf86cd799439011', '507f1f77bcf86cd799439012']]],
                'expectation' => [
                    '_id' => [
                        '$in' => [
                            new ObjectID('507f1f77bcf86cd799439011'),
                            new ObjectID('507f1f77bcf86cd799439012'),
                        ],
                    ],
                ],
            ],
            // ------------------------
            'Series of string _ids as the $in parameter' => [
                'value' => ['_id' => ['$nin' => ['507f1f77bcf86cd799439011']]],
                'expectation' => ['_id' => ['$nin' => [new ObjectID('507f1f77bcf86cd799439011')]]],
            ],
        ];
    }

    public function getWriteConcernVariations()
    {
        return [
            'acknowledged write concern with plain object' => [
                'object' => m::mock(),
                'writeConcern' => 1,
                'shouldFireEventAfter' => true,
                'expected' => true,
            ],
            'acknowledged write concern with attributesAccessIntesarface' => [
                'object' => m::mock(AttributesAccessInterface::class),
                'writeConcern' => 1,
                'shouldFireEventAfter' => true,
                'expected' => true,
            ],
            'unacknowledged write concern with plain object' => [
                'object' => m::mock(),
                'writeConcern' => 0,
                'shouldFireEventAfter' => false,
                'expected' => false,
            ],
            'unacknowledged write concern with attributesAccessInterface' => [
                'object' => m::mock(AttributesAccessInterface::class),
                'writeConcern' => 0,
                'shouldFireEventAfter' => false,
                'expected' => false,
            ],
        ];
    }

    /**
     * Retrieves projections that should be replaced by mapper.
     */
    public function getProjections()
    {
        return [
            'Should return self array' => [
                'projection' => ['some' => true, 'fields' => false],
                'expected' => ['some' => true, 'fields' => false],
            ],
            'Should convert number' => [
                'projection' => ['some' => 1, 'fields' => -1],
                'expected' => ['some' => true, 'fields' => false],
            ],
            'Should add true in fields' => [
                'projection' => ['some', 'fields'],
                'expected' => ['some' => true, 'fields' => true],
            ],
            'Should add boolean values according to key value' => [
                'projection' => ['-some', 'fields'],
                'expected' => ['some' => false, 'fields' => true],
            ],
        ];
    }
}
