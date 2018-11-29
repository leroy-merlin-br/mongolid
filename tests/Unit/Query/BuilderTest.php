<?php
namespace Mongolid\Query;

use InvalidArgumentException;
use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Cursor\Cursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\AbstractModel;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\ModelInterface;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\ReplaceCollectionModel;

class BuilderTest extends TestCase
{
    public function testShouldBeAbleToConstruct()
    {
        // Set
        $connection = m::mock(Connection::class);

        // Actions
        $builder = new Builder($connection);

        // Assertions
        $this->assertAttributeSame($connection, 'connection', $builder);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldSave(ReplaceCollectionModel $model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->setCollection($collection);

        // Expectations
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

        // Actions
        $result = $builder->save($model, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldInsert(ReplaceCollectionModel $model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->setCollection($collection);
        $model->_id = null;

        // Expectations
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

        // Actions
        $result = $builder->insert($model, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldInsertWithoutFiringEvents(
        ReplaceCollectionModel $model,
        $writeConcern,
        $shouldFireEventAfter,
        $expected
    ) {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->setCollection($collection);
        $model->_id = null;

        // Expectations
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

        // Actions
        $result = $builder->insert($model, $options, false);

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldUpdate(ReplaceCollectionModel $model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $model->setCollection($collection);

        // Expectations
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

        // Actions
        $result = $builder->update($model, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldUpdateUnsettingFields()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $model = new class() extends ReplaceCollectionModel
        {
            /**
             * {@inheritdoc}
             */
            public $fillable = [
                'name',
                'unchanged',
            ];

            /**
             * {@inheritdoc}
             */
            protected $dynamic = false;
        };
        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern(1)];
        $model->setCollection($collection);

        $model->unchanged = 'unchanged';
        $model->notOnFillable = 'to be deleted';
        $model->name = 'John';
        $model->syncOriginalDocumentAttributes();
        $model->_id = 123;
        unset($model->name);

        // Expectations
        $collection->expects()
            ->updateOne(
                ['_id' => 123],
                ['$set' => ['_id' => 123], '$unset' => ['name' => '', 'notOnFillable' => '']],
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

        // Actions
        $result = $builder->update($model, $options);

        // Assertions
        $this->assertTrue($result);
    }

    public function testUpdateShouldCalculateChangesAccordingly()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $model = new class() extends ReplaceCollectionModel
        {
        };
        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern(1)];
        $model->setCollection($collection);

        $model->unchanged = 'unchanged';
        $model->name = 'John';
        $model->surname = 'Doe';
        $model->addresses = ['1 Blue Street'];
        $model->syncOriginalDocumentAttributes();
        $model->_id = 123;
        unset($model->name);
        $model->surname = ['Doe', 'Jr'];
        $model->addresses = ['1 Blue Street', '2 Green Street'];

        // Expectations
        $collection->expects()
            ->updateOne(
                ['_id' => 123],
                [
                    '$set' => ['_id' => 123, 'surname' => ['Doe', 'Jr'], 'addresses.1' => '2 Green Street'],
                    '$unset' => ['name' => ''],
                ],
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

        // Actions
        $result = $builder->update($model, $options);

        // Assertions
        $this->assertTrue($result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testUpdateShouldCallInsertWhenObjectHasNoId(
        ReplaceCollectionModel $model,
        $writeConcern,
        $shouldFireEventAfter,
        $expected
    ) {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $model->setCollection($collection);
        $model->_id = null;

        // Actions
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

        // Actions
        $result = $builder->update($model, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldDelete(ReplaceCollectionModel $model, $writeConcern, $shouldFireEventAfter, $expected)
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $model->setCollection($collection);

        // Expectations
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

        // Actions
        $result = $builder->delete($model, $options);

        // Assertions
        $this->assertSame($expected, $result);
    }

    /**
     * @dataProvider eventsToBailOperations
     */
    public function testDatabaseOperationsShouldBailOutIfTheEventHandlerReturnsFalse(
        $operation,
        $dbOperation,
        $eventName
    ) {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);
        $collection = m::mock(Collection::class);
        $model = m::mock(ModelInterface::class);

        $builder->shouldAllowMockingProtectedMethods();

        // Expectations
        $builder->allows()
            ->getCollection($model)
            ->andReturn($collection);

        $collection->expects($dbOperation)
            ->never();

        /* "Mocks" the fireEvent to return false and bail the operation */
        $this->expectEventToBeFired($eventName, $model, true, false);

        // Actions
        $result = $builder->$operation($model);

        // Assertions
        $this->assertFalse($result);
    }

    public function testShouldGetWithWhereQuery()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();

        $collection = m::mock(Collection::class);
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $projection = ['project' => true, '_id' => false, '__pclass' => true];

        // Expectations
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        // Actions
        $result = $builder->where($model, $query, $projection);

        // Assertions
        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertAttributeSame($collection, 'collection', $result);
        $this->assertAttributeSame('find', 'command', $result);
        $this->assertAttributeSame(
            [$preparedQuery, ['projection' => $projection]],
            'params',
            $result
        );
    }

    public function testShouldGetAll()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[where]', [$connection]);
        $mongolidCursor = m::mock(Cursor::class);
        $model = m::mock(ModelInterface::class);

        // Expectations
        $builder->expects()
            ->where($model, [])
            ->andReturn($mongolidCursor);

        // Actions
        $result = $builder->all($model);

        // Assertions
        $this->assertSame($mongolidCursor, $result);
    }

    public function testShouldGetFirstWithQuery()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn($model);

        // Actions
        $result = $builder->first($model, $query);

        // Assertions
        $this->assertSame($model, $result);
    }

    public function testFirstWithNullShouldNotHitTheDatabase()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Actions
        $result = $builder->first(m::mock(ModelInterface::class), null);

        // Assertions
        $this->assertNull($result);
    }

    public function testFirstOrFailShouldGetFirst()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn($model);

        // Actions
        $result = $builder->firstOrFail($model, $query);

        // Assertions
        $this->assertSame($model, $result);
    }

    public function testFirstOrFailWithNullShouldFail()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $model = new class extends AbstractModel
        {
        };

        // Expectations
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model ['.get_class($model).'].');

        // Actions
        $builder->firstOrFail($model, null);
    }

    public function testShouldGetNullIfFirstCantFindAnything()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => []])
            ->andReturn(null);

        // Actions
        $result = $builder->first($model, $query);

        // Assertions
        $this->assertNull($result);
    }

    public function testShouldGetFirstProjectingFields()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[prepareValueQuery]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = ['_id' => 123];
        $projection = ['project' => true, 'fields' => false, '__pclass' => true];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $builder->expects()
            ->prepareValueQuery($query)
            ->andReturn($preparedQuery);

        $collection->expects()
            ->findOne($preparedQuery, ['projection' => $projection])
            ->andReturn(null);

        // Actions
        $result = $builder->first($model, $query, $projection);

        // Assertions
        $this->assertNull($result);
    }

    /**
     * @dataProvider queryValueScenarios
     */
    public function testShouldPrepareQueryValue($value, $expectation)
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Actions
        $result = $this->callProtected($builder, 'prepareValueQuery', [$value]);

        // Assertions
        $this->assertEquals($expectation, $result, 'Queries are not equals');
    }

    /**
     * @dataProvider getProjections
     */
    public function testPrepareProjectionShouldConvertArray($data, $expectation)
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Actions
        $result = $this->callProtected($builder, 'prepareProjection', [$data]);

        // Assertions
        $this->assertSame($expectation, $result);
    }

    public function testPrepareProjectionShouldThrownAnException()
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $data = ['valid' => true, 'invalid-key' => 'invalid-value'];

        // Expectations
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid projection: 'invalid-key' => 'invalid-value'");

        // Actions
        $this->callProtected($builder, 'prepareProjection', [$data]);
    }

    public function eventsToBailOperations(): array
    {
        return [
            'Saving event' => [
                'operation' => 'save',
                'dbOperation' => 'replaceOne',
                'eventName' => 'saving',
            ],
            'Inserting event' => [
                'operation' => 'insert',
                'dbOperation' => 'insertOne',
                'eventName' => 'inserting',
            ],
            'Updating event' => [
                'operation' => 'update',
                'dbOperation' => 'updateOne',
                'eventName' => 'updating',
            ],
            'Deleting event' => [
                'operation' => 'delete',
                'dbOperation' => 'deleteOne',
                'eventName' => 'deleting',
            ],
        ];
    }

    public function queryValueScenarios(): array
    {
        return [
            'An array' => [
                'value' => ['age' => ['$gt' => 25]],
                'expectation' => ['age' => ['$gt' => 25]],
            ],
            'An ObjectId string' => [
                'value' => '507f1f77bcf86cd799439011',
                'expectation' => ['_id' => new ObjectId('507f1f77bcf86cd799439011')],
            ],
            'An ObjectId string within a query' => [
                'value' => ['_id' => '507f1f77bcf86cd799439011'],
                'expectation' => ['_id' => new ObjectId('507f1f77bcf86cd799439011')],
            ],
            'Other type of _id, sequence for example' => [
                'value' => 7,
                'expectation' => ['_id' => 7],
            ],
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
            'Series of string _ids as the $nin parameter' => [
                'value' => ['_id' => ['$nin' => ['507f1f77bcf86cd799439011']]],
                'expectation' => ['_id' => ['$nin' => [new ObjectId('507f1f77bcf86cd799439011')]]],
            ],
        ];
    }

    public function getWriteConcernVariations(): array
    {
        $model = new ReplaceCollectionModel();
        $model2 = new ReplaceCollectionModel();
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
    public function getProjections(): array
    {
        return [
            'Should return self array' => [
                'projection' => ['some' => true, 'fields' => false],
                'expected' => ['some' => true, 'fields' => false, '__pclass' => true],
            ],
            'Should convert number' => [
                'projection' => ['some' => 1, 'fields' => -1],
                'expected' => ['some' => true, 'fields' => false, '__pclass' => true],
            ],
            'Should add true in fields' => [
                'projection' => ['some', 'fields'],
                'expected' => ['some' => true, 'fields' => true, '__pclass' => true],
            ],
            'Should add boolean values according to key value' => [
                'projection' => ['-some', 'fields'],
                'expected' => ['some' => false, 'fields' => true, '__pclass' => true],
            ],
            'Should not exclude __pclass from projection' => [
                'projection' => ['fields' => true, '__pclass' => false],
                'expected' => ['fields' => true, '__pclass' => true],
            ],
            'Empty should not include __pclass' => [
                'projection' => [],
                'expected' => [],
            ],
        ];
    }

    protected function getEventService(): EventTriggerService
    {
        if (!Container::has(EventTriggerService::class)) {
            Container::instance(EventTriggerService::class, m::mock(EventTriggerService::class));
        }

        return Container::make(EventTriggerService::class);
    }

    protected function expectEventToBeFired(string $event, ModelInterface $model, bool $halt, bool $return = true): void
    {
        $event = 'mongolid.'.$event.': '.get_class($model);

        $this->getEventService()
            ->expects()
            ->fire($event, $model, $halt)
            ->andReturn($return);
    }

    protected function expectEventNotToBeFired(string $event, ModelInterface $model): void
    {
        $event = 'mongolid.'.$event.': '.get_class($model);

        $this->getEventService()
            ->expects()
            ->fire($event, $model, m::any())
            ->never();
    }
}
