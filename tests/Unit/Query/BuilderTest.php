<?php
namespace Mongolid\Query;

use InvalidArgumentException;
use Mockery as m;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Cursor\CacheableCursor;
use Mongolid\Cursor\Cursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\AbstractModel;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\ModelInterface;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\ReplaceCollectionModel;

final class BuilderTest extends TestCase
{
    public function testShouldBeAbleToConstruct(): void
    {
        // Set
        $connection = m::mock(Connection::class);

        // Actions
        $builder = new Builder($connection);
        $result = $this->getProtected($builder, 'connection');

        // Assertions
        $this->assertSame($connection, $result);
    }

    /**
     * @dataProvider getWriteConcernVariations
     */
    public function testShouldSave(
        ReplaceCollectionModel $model,
        int $writeConcern,
        bool $shouldFireEventAfter,
        bool $expected
    ): void {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->setCollection($collection);

        // Expectations
        $collection
            ->expects('replaceOne')
            ->with(
                ['_id' => 123],
                $model,
                ['upsert' => true, 'writeConcern' => new WriteConcern($writeConcern)]
            )->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn((bool) $writeConcern);

        $operationResult
            ->allows('getModifiedCount')
            ->withNoArgs()
            ->andReturn(1);

        $operationResult
            ->allows('getUpsertedCount')
            ->withNoArgs()
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
    public function testShouldInsert(
        ReplaceCollectionModel $model,
        int $writeConcern,
        bool $shouldFireEventAfter,
        bool $expected
    ): void {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();

        $model->setCollection($collection);
        $model->_id = null;

        // Expectations
        $collection
            ->expects('insertOne')
            ->with($model, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn((bool) $writeConcern);

        $operationResult
            ->allows('getInsertedCount')
            ->withNoArgs()
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
        int $writeConcern,
        bool $shouldFireEventAfter,
        bool $expected
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
        $collection
            ->expects('insertOne')
            ->with($model, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn((bool) $writeConcern);

        $operationResult
            ->allows('getInsertedCount')
            ->withNoArgs()
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
    public function testShouldUpdate(
        ReplaceCollectionModel $model,
        int $writeConcern,
        bool $shouldFireEventAfter,
        bool $expected
    ): void {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $parsedObject = ['_id' => 123];
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $model->setCollection($collection);

        // Expectations
        $collection
            ->expects('updateOne')
            ->with(['_id' => 123], ['$set' => $parsedObject], $options)
            ->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn((bool) $writeConcern);

        $operationResult
            ->allows('getModifiedCount')
            ->withNoArgs()
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

    public function testShouldUpdateUnsettingFields(): void
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
        $collection
            ->expects('updateOne')
            ->with(
                ['_id' => 123],
                ['$set' => ['_id' => 123], '$unset' => ['name' => '', 'notOnFillable' => '']],
                $options
            )->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn(true);

        $operationResult
            ->allows('getModifiedCount')
            ->withNoArgs()
            ->andReturn(1);

        $this->expectEventToBeFired('updating', $model, true);

        $this->expectEventToBeFired('updated', $model, false);

        // Actions
        $result = $builder->update($model, $options);

        // Assertions
        $this->assertTrue($result);
    }

    public function testUpdateShouldCalculateChangesAccordingly(): void
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
        $collection
            ->expects('updateOne')
            ->with(
                ['_id' => 123],
                [
                    '$set' => ['_id' => 123, 'surname' => ['Doe', 'Jr'], 'addresses.1' => '2 Green Street'],
                    '$unset' => ['name' => ''],
                ],
                $options
            )->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn(true);

        $operationResult
            ->allows('getModifiedCount')
            ->withNoArgs()
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
        int $writeConcern,
        bool $shouldFireEventAfter,
        bool $expected
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
        $collection
            ->expects('insertOne')
            ->with($model, ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn((bool) $writeConcern);

        $operationResult
            ->allows('getInsertedCount')
            ->withNoArgs()
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
    public function testShouldDelete(
        ReplaceCollectionModel $model,
        int $writeConcern,
        bool $shouldFireEventAfter,
        bool $expected
    ): void {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $operationResult = m::mock();
        $options = ['writeConcern' => new WriteConcern($writeConcern)];

        $model->setCollection($collection);

        // Expectations
        $collection
            ->expects('deleteOne')
            ->with(['_id' => 123], ['writeConcern' => new WriteConcern($writeConcern)])
            ->andReturn($operationResult);

        $operationResult
            ->expects('isAcknowledged')
            ->withNoArgs()
            ->andReturn((bool) $writeConcern);

        $operationResult
            ->allows('getDeletedCount')
            ->with()
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
        string $operation,
        string $dbOperation,
        string $eventName
    ) {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[getCollection]', [$connection]);
        $collection = m::mock(Collection::class);
        $model = m::mock(ModelInterface::class);

        $builder->shouldAllowMockingProtectedMethods();

        // Expectations
        $builder
            ->allows('getCollection')
            ->with($model)
            ->andReturn($collection);

        $collection
            ->expects($dbOperation)
            ->never();

        /* "Mocks" the fireEvent to return false and bail the operation */
        $this->expectEventToBeFired($eventName, $model, true, false);

        // Actions
        $result = $builder->$operation($model);

        // Assertions
        $this->assertFalse($result);
    }

    public function testShouldGetWithWhereQuery(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $projection = ['project' => true, '_id' => false, '__pclass' => true];

        // Actions
        $result = $builder->where($model, $query, $projection);
        $collectionResult = $this->getProtected($result, 'collection');
        $commandResult = $this->getProtected($result, 'command');
        $paramsResult = $this->getProtected($result, 'params');

        // Assertions
        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertSame($collection, $collectionResult);
        $this->assertSame('find', $commandResult);
        $this->assertSame([$preparedQuery, ['projection' => $projection, 'eagerLoads' => []]], $paramsResult);
    }

    public function testShouldGetWithWhereQueryEagerLoadingModels(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $model = new ReplaceCollectionModel();
        $model->with = [
            'other' => [
                'key' => 'some key',
                'model' => 'Some/Model',
            ],
        ];
        $model->setCollection($collection);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $projection = ['project' => true, '_id' => false, '__pclass' => true];
        $expectedParams = [
            $preparedQuery,
            [
                'projection' => $projection,
                'eagerLoads' => [
                    'other' => [
                        'key' => 'some key',
                        'model' => 'Some/Model',
                    ],
                ],
            ],
        ];

        // Expectations


        // Actions
        $result = $builder->where($model, $query, $projection);
        $collectionResult = $this->getProtected($result, 'collection');
        $commandResult = $this->getProtected($result, 'command');
        $paramsResult = $this->getProtected($result, 'params');

        // Assertions
        $this->assertInstanceOf(Cursor::class, $result);
        $this->assertSame($collection, $collectionResult);
        $this->assertSame('find', $commandResult);
        $this->assertSame($expectedParams, $paramsResult);
    }

    public function testShouldGetCacheableCursor(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $projection = ['project' => true, '_id' => false, '__pclass' => true];

        // Actions
        $result = $builder->where($model, $query, $projection, true);
        $collectionResult = $this->getProtected($result, 'collection');
        $commandResult = $this->getProtected($result, 'command');
        $paramsResult = $this->getProtected($result, 'params');

        // Assertions
        $this->assertInstanceOf(CacheableCursor::class, $result);
        $this->assertSame($collection, $collectionResult);
        $this->assertSame('find', $commandResult);
        $this->assertSame([$preparedQuery, ['projection' => $projection, 'eagerLoads' => []]], $paramsResult);
    }

    public function testShouldGetAll(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[where]', [$connection]);
        $mongolidCursor = m::mock(Cursor::class);
        $model = m::mock(ModelInterface::class);

        // Expectations
        $builder
            ->expects('where')
            ->with($model, [])
            ->andReturn($mongolidCursor);

        // Actions
        $result = $builder->all($model);

        // Assertions
        $this->assertSame($mongolidCursor, $result);
    }

    public function testShouldGetFirstWithQuery(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $collection->expects('findOne')
            ->with($preparedQuery, ['projection' => []])
            ->andReturn($model);


        // Actions
        $result = $builder->first($model, $query);

        // Assertions
        $this->assertSame($model, $result);
    }

    public function testShouldGetFirstWithCachedResults(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[where]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();
        $collection = m::mock(Collection::class);
        $query = 123;
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);
        $cursor = m::mock(Cursor::class);

        // Expectations
        $builder
            ->expects('where')
            ->with($model, $query, [], true)
            ->andReturn($cursor);

        $cursor->expects()
            ->first()
            ->andReturn($model);

        // Actions
        $result = $builder->first($model, $query, [], true);

        // Assertions
        $this->assertSame($model, $result);
    }

    public function testFirstWithNullShouldNotHitTheDatabase(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Actions
        $result = $builder->first(m::mock(ModelInterface::class), null);

        // Assertions
        $this->assertNull($result);
    }

    public function testFirstOrFailShouldGetFirst(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $collection
            ->expects('findOne')
            ->with($preparedQuery, ['projection' => []])
            ->andReturn($model);

        // Actions
        $result = $builder->firstOrFail($model, $query);

        // Assertions
        $this->assertSame($model, $result);
    }

    public function testShouldGetCachedResultsWhenCallingFirstOrFail(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = m::mock(Builder::class.'[where]', [$connection]);
        $builder->shouldAllowMockingProtectedMethods();
        $collection = m::mock(Collection::class);
        $query = 123;
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);
        $cursor = m::mock(Cursor::class);

        // Expectations
        $builder
            ->expects('where')
            ->with($model, $query, [], true)
            ->andReturn($cursor);

        $cursor->expects()
            ->first()
            ->andReturn($model);

        // Actions
        $result = $builder->firstOrFail($model, $query, [], true);

        // Assertions
        $this->assertSame($model, $result);
    }

    public function testFirstOrFailWithNullShouldFail(): void
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

    public function testShouldGetNullIfFirstCantFindAnything(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);
        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $collection
            ->expects('findOne')
            ->with($preparedQuery, ['projection' => []])
            ->andReturn(null);

        // Actions
        $result = $builder->first($model, $query);

        // Assertions
        $this->assertNull($result);
    }

    public function testShouldGetFirstProjectingFields(): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        $collection = m::mock(Collection::class);
        $query = 123;
        $preparedQuery = [
            '_id' => 123,
            'deleted_at' => ['$exists' => false],
        ];
        $projection = ['project' => true, 'fields' => false, '__pclass' => true];
        $model = new ReplaceCollectionModel();
        $model->setCollection($collection);

        // Expectations
        $collection
            ->expects('findOne')
            ->with($preparedQuery, ['projection' => $projection])
            ->andReturn(null);

        // Actions
        $result = $builder->first($model, $query, $projection);

        // Assertions
        $this->assertNull($result);
    }

    /**
     * @dataProvider getProjections
     */
    public function testPrepareProjectionShouldConvertArray($data, $expectation): void
    {
        // Set
        $connection = m::mock(Connection::class);
        $builder = new Builder($connection);

        // Actions
        $result = $this->callProtected($builder, 'prepareProjection', [$data]);

        // Assertions
        $this->assertSame($expectation, $result);
    }

    public function testPrepareProjectionShouldThrownAnException(): void
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
