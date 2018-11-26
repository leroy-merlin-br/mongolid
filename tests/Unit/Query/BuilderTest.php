<?php
namespace Mongolid\Query;

use InvalidArgumentException;
use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\Cursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\AbstractModel;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\ModelInterface;
use Mongolid\Schema\DynamicSchema;
use Mongolid\TestCase;

class BuilderTest extends TestCase
{
    public function testShouldBeAbleToConstructWithSchema()
    {
        // Arrange
        $connection = m::mock(Connection::class);

        // Act
        $builder = new Builder($connection);

        // Assert
        $this->assertAttributeEquals($connection, 'connection', $builder);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldSave($model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        // Act
        $builder->shouldAllowMockingProtectedMethods();

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->replaceOne(
                ['_id' => 123],
                $model,
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

        $this->expectEventToBeFired('saving', $model, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('saved', $model, false);
        } else {
            $this->expectEventNotToBeFired('saved', $model);
        }

        // Assert
        $this->assertSame($expected, $builder->save($model, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldInsert($model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->_id = null;

        // Act
        $builder->shouldAllowMockingProtectedMethods();

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->insertOne($model, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getInsertedCount()
            ->andReturn(1);

        $this->expectEventToBeFired('inserting', $model, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('inserted', $model, false);
        } else {
            $this->expectEventNotToBeFired('inserted', $model);
        }

        // Assert
        $this->assertSame($expected, $builder->insert($model, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldInsertWithoutFiringEvents($model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->_id = null;

        // Act
        $builder->shouldAllowMockingProtectedMethods();

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->insertOne($model, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getInsertedCount()
            ->andReturn(1);

        $this->expectEventNotToBeFired('inserting', $model);
        $this->expectEventNotToBeFired('inserted', $model);

        // Assert
        $this->assertSame($expected, $builder->insert($model, $options, false));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldUpdate($model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $builder = new Builder($connection);
        $builder->setSchema($model->getSchema());

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        // Expect
        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('')
            ->andReturn($collection);

        $collection->expects()
            ->updateOne(
                ['_id' => 123],
                ['$set' => $parsedObject],
                $options
            )->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getModifiedCount()
            ->andReturn(1);

        $this->expectEventToBeFired('updating', $model, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('updated', $model, false);
        } else {
            $this->expectEventNotToBeFired('updated', $model);
        }

        // Act
        $result = $builder->update($model, $options);

        // Assert
        $this->assertSame($expected, $result);
    }

    public function testShouldUpdateUnsettingFields()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $client = m::mock(Client::class);
        $database = m::mock(Database::class);
        $builder = new Builder($connection);

        $model = new class extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            public $fields = [
                '_id' => 'objectId',
                'unchanged' => 'string',
            ];
        };
        $builder->setSchema($model->getSchema());
        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern(1)];

        $model->unchanged = 'unchanged';
        $model->notOnSchema = 'to be deleted';
        $model->name = 'John';
        $model->syncOriginalDocumentAttributes();
        $model->_id = 123;
        unset($model->name);

        // Expect
        $connection->expects()
            ->getRawConnection()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('mongolid')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('')
            ->andReturn($collection);

        $collection->expects()
            ->updateOne(
                ['_id' => 123],
                ['$set' => ['_id' => 123], '$unset' => ['name' => '']],
                $options
            )->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn(true);

        $operationResult->allows()
            ->getModifiedCount()
            ->andReturn(1);

        $this->expectEventToBeFired('updating', $model, true);

        $this->expectEventToBeFired('updated', $model, false);

        // Act
        $result = $builder->update($model, $options);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testUpdateShouldCallInsertWhenObjectHasNoId(
        $model,
        $writeConcern,
        $shouldFireEventAfter,
        $expected
    ) {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $model->_id = null;

        // Act
        $builder->shouldAllowMockingProtectedMethods();

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->insertOne(
                $model,
                ['writeConcern' => new WriteConcern($writeConcern)]
            )->andReturn($operationResult);

        $operationResult->expects()
            ->isAcknowledged()
            ->andReturn((bool) $writeConcern);

        $operationResult->allows()
            ->getInsertedCount()
            ->andReturn(1);

        $this->expectEventToBeFired('updating', $model, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('updated', $model, false);
        } else {
            $this->expectEventNotToBeFired('updated', $model);
        }

        $this->expectEventNotToBeFired('inserting', $model);
        $this->expectEventNotToBeFired('inserted', $model);

        // Assert
        $this->assertSame($expected, $builder->update($model, $options));
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldDelete($model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        // Act
        $builder->shouldAllowMockingProtectedMethods();

        $builder->expects()
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

        $this->expectEventToBeFired('deleting', $model, true);

        if ($shouldFireEventAfter) {
            $this->expectEventToBeFired('deleted', $model, false);
        } else {
            $this->expectEventNotToBeFired('deleted', $model);
        }

        // Assert
        $this->assertSame($expected, $builder->delete($model, $options));
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
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);
        $collection = m::mock(Collection::class);
        $model = m::mock(ModelInterface::class);

        $builder->shouldAllowMockingProtectedMethods();

        // Expect
        $builder->allows()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects($dbOperation)
            ->never();

        /* "Mocks" the fireEvent to return false and bail the operation */
        $this->expectEventToBeFired($eventName, $model, true, false);

        // Act
        $result = $builder->$operation($model);

        // Assert
        $this->assertFalse($result);
    }

    public function testShouldGetWithWhereQuery()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery,getCollection]', [$connection]);
        $schema = m::mock(DynamicSchema::class);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $projection = ['project' => true, '_id' => false];

        $schema->modelClass = 'stdClass';
        $builder->setSchema($schema);

        $builder->shouldAllowMockingProtectedMethods();

        // Expect
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        // Act
        $result = $builder->where($query, $projection);

        // Assert
        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertAttributeEquals($schema, 'modelSchema', $result);
        $this->assertAttributeEquals($collection, 'collection', $result);
        $this->assertAttributeEquals('find', 'command', $result);
        $this->assertAttributeEquals(
            [$preparedQuery, ['projection' => $projection]],
            'params',
            $result
        );
    }

    public function testShouldGetAll()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[where]', [$connection]);
        $mongolidCursor = m::mock(Cursor::class);

        // Expect
        $builder->expects()
            ->where([])
            ->andReturn($mongolidCursor);

        // Act
        $result = $builder->all();

        // Assert
        $this->assertSame($mongolidCursor, $result);
    }

    public function testShouldGetFirstWithQuery()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery,getCollection]', [$connection]);
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $object = new class extends AbstractModel
        {
        };
        $builder->shouldAllowMockingProtectedMethods();

        // Act
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn($object);

        $result = $builder->first($query);

        // Assert
        $this->assertSame($object, $result);
    }

    public function testFirstWithNullShouldNotHitTheDatabase()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Act
        $result = $builder->first(null);

        // Assert
        $this->assertNull($result);
    }

    public function testFirstOrFailShouldGetFirst()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery,getCollection]', [$connection]);
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $object = new class extends AbstractModel
        {
        };

        $builder->shouldAllowMockingProtectedMethods();

        // Act
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn($object);

        $result = $builder->firstOrFail($query);

        // Assert
        $this->assertSame($object, $result);
    }

    public function testFirstOrFailWithNullShouldFail()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $builder->setSchema(
            new class extends DynamicSchema
            {
                /**
                 * {@inheritdoc}
                 */
                public $modelClass = 'User';
            }
        );

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [User].');

        // Act
        $builder->firstOrFail(null);
    }

    public function testShouldGetNullIfFirstCantFindAnything()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery,getCollection]', [$connection]);
        $schema = m::mock(DynamicSchema::class);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];

        $schema->modelClass = 'stdClass';
        $builder->setSchema($schema);

        $builder->shouldAllowMockingProtectedMethods();

        // Expect
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn(null);

        // Act
        $result = $builder->first($query);

        // Assert
        $this->assertNull($result);
    }

    public function testShouldGetFirstProjectingFields()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = m::mock(
            Builder::class.'[prepareValueQuery,getCollection]',
            [$connection]
        );
        $schema = m::mock(DynamicSchema::class);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $projection = ['project' => true, 'fields' => false];

        $schema->modelClass = 'stdClass';
        $builder->setSchema($schema);

        $builder->shouldAllowMockingProtectedMethods();

        // Expect
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $builder->expects()
            ->getCollection()
            ->andReturn($collection);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => $projection])
            ->andReturn(null);

        // Act
        $result = $builder->first($query, $projection);

        // Assert
        $this->assertNull($result);
    }

    public function testShouldGetSchemaMapper()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $builder->schemaClass = 'MySchema';
        $schema = $this->instance('MySchema', m::mock(DynamicSchema::class));

        // Act
        $result = $this->callProtected($builder, 'getSchemaMapper');

        // Assert
        $this->assertInstanceOf(SchemaMapper::class, $result);
        $this->assertSame($schema, $result->schema);
    }

    public function testShouldGetRawCollection()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $collection = m::mock(Collection::class);
        $schema = m::mock(DynamicSchema::class);
        $schema->collection = 'foobar';

        $builder->setSchema($schema);
        $connection->defaultDatabase = 'grimory';
        $connection->grimory = (object) ['foobar' => $collection];

        // Expect
        $connection->expects()
            ->getRawConnection()
            ->andReturn($connection);

        // Act
        $result = $this->callProtected($builder, 'getCollection');

        // Assert
        $this->assertSame($collection, $result);
    }

    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue($value, $expectation)
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Act
        $result = $this->callProtected($builder, 'prepareValueQuery', [$value]);

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
        $builder = new Builder($connection);

        // Act
        $result = $this->callProtected($builder, 'prepareProjection', [$data]);

        // Assert
        $this->assertSame($expectation, $result);
    }

    public function testPrepareProjectionShouldThrownAnException()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $data = ['valid' => true, 'invalid-key' => 'invalid-value'];

        // Expectations
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid projection: 'invalid-key' => 'invalid-value'");

        // Act
        $this->callProtected($builder, 'prepareProjection', [$data]);
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
                'expectation' => ['_id' => new ObjectId('507f1f77bcf86cd799439011')],
            ],
            // ------------------------
            'An ObjectId string within a query' => [
                'value' => ['_id' => '507f1f77bcf86cd799439011'],
                'expectation' => ['_id' => new ObjectId('507f1f77bcf86cd799439011')],
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
                            new ObjectId('507f1f77bcf86cd799439011'),
                            new ObjectId('507f1f77bcf86cd799439012'),
                        ],
                    ],
                ],
            ],
            // ------------------------
            'Series of string _ids as the $in parameter' => [
                'value' => ['_id' => ['$nin' => ['507f1f77bcf86cd799439011']]],
                'expectation' => ['_id' => ['$nin' => [new ObjectId('507f1f77bcf86cd799439011')]]],
            ],
        ];
    }

    public function getWriteConcernVariations()
    {
        $model = new class extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            public $fields = [
                '_id' => 'objectId',
            ];
        };

        $model2 = new class extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            public $fields = [
                '_id' => 'objectId',
            ];
        };

        $model->_id = 123;
        $model2->_id = 123;

        return [
            'acknowledged write concern' => [
                'object' => $model,
                'writeConcern' => 1,
                'shouldFireEventAfter' => true,
                'expected' => true,
            ],
            'unacknowledged write concern' => [
                'object' => $model2,
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

    protected function getEventService()
    {
        if (!Ioc::has(EventTriggerService::class)) {
            Ioc::instance(EventTriggerService::class, m::mock(EventTriggerService::class));
        }

        return Ioc::make(EventTriggerService::class);
    }

    protected function expectEventToBeFired($event, $model, bool $halt, $return = true)
    {
        $event = 'mongolid.'.$event.': '.get_class($model);

        $this->getEventService()->expects()
            ->fire($event, $model, $halt)
            ->andReturn($return);
    }

    protected function expectEventNotToBeFired($event, $model)
    {
        $event = 'mongolid.'.$event.': '.get_class($model);

        $this->getEventService()->expects()
            ->fire($event, $model, m::any())
            ->never();
    }
}
