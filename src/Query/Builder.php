<?php
namespace Mongolid\Query;

use InvalidArgumentException;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Cursor\CacheableCursor;
use Mongolid\Cursor\Cursor;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Cursor\EagerLoadingCursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\ModelInterface;

/**
 * This class will abstract how a Model is persisted and retrieved
 * from the database.
 */
class Builder
{
    private bool $ignoreSoftDelete = false;

    /**
     * Connection that is going to be used to interact with the database.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * In order to dispatch events when necessary.
     *
     * @var EventTriggerService
     */
    protected $eventService;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Upserts the given object into database. Returns success if write concern
     * is acknowledged.
     *
     * Notice: Saves with Unacknowledged WriteConcern will not fire `saved` event.
     * Return is always false if write concern is Unacknowledged.
     *
     * @param ModelInterface $model   the model used in the operation
     * @param array          $options possible options to send to mongo driver
     */
    public function save(ModelInterface $model, array $options = []): bool
    {
        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This gives an opportunities to
        // listeners to cancel save operations if validations fail or whatever.
        if (false === $this->fireEvent('saving', $model, true)) {
            return false;
        }

        $model->bsonSerialize();

        $queryResult = $model->getCollection()->replaceOne(
            ['_id' => $model->_id],
            $model,
            $this->mergeOptions($options, ['upsert' => true])
        );

        $result = $queryResult->isAcknowledged() &&
                  ($queryResult->getModifiedCount() || $queryResult->getUpsertedCount());

        if ($result) {
            $this->afterSuccess($model);

            $this->fireEvent('saved', $model);
        }

        return $result;
    }

    /**
     * Inserts the given object into database. Returns success if write concern
     * is acknowledged. Since it's an insert, it will fail if the _id already
     * exists.
     *
     * Notice: Inserts with Unacknowledged WriteConcern will not fire `inserted` event.
     * Return is always false if write concern is Unacknowledged.
     *
     * @param ModelInterface $model      the model used in the operation
     * @param array          $options    possible options to send to mongo driver
     * @param bool           $fireEvents whether events should be fired
     */
    public function insert(ModelInterface $model, array $options = [], bool $fireEvents = true): bool
    {
        if ($fireEvents && false === $this->fireEvent('inserting', $model, true)) {
            return false;
        }

        $queryResult = $model->getCollection()->insertOne(
            $model,
            $this->mergeOptions($options)
        );

        $result = $queryResult->isAcknowledged() && $queryResult->getInsertedCount();

        if ($result) {
            $this->afterSuccess($model);

            if ($fireEvents) {
                $this->fireEvent('inserted', $model);
            }
        }

        return $result;
    }

    /**
     * Updates the given object into database. Returns success if write concern
     * is acknowledged. Since it's an update, it will fail if the model with
     * the given _id did not exists.
     *
     * Notice: Updates with Unacknowledged WriteConcern will not fire `updated` event.
     * Return is always false if write concern is Unacknowledged.
     *
     * @param ModelInterface $model   the model used in the operation
     * @param array          $options possible options to send to mongo driver
     */
    public function update(ModelInterface $model, array $options = []): bool
    {
        if (false === $this->fireEvent('updating', $model, true)) {
            return false;
        }

        if (!$model->_id) {
            if ($result = $this->insert($model, $options, false)) {
                $this->afterSuccess($model);

                $this->fireEvent('updated', $model);
            }

            return $result;
        }

        $updateData = $this->getUpdateData($model, $model->bsonSerialize());

        $queryResult = $model->getCollection()->updateOne(
            ['_id' => $model->_id],
            $updateData,
            $this->mergeOptions($options)
        );

        $result = $queryResult->isAcknowledged() && $queryResult->getModifiedCount();

        if ($result) {
            $this->afterSuccess($model);

            $this->fireEvent('updated', $model);
        }

        return $result;
    }

    /**
     * Removes the given document from the collection.
     *
     * Notice: Deletes with Unacknowledged WriteConcern will not fire `deleted` event.
     * Return is always false if write concern is Unacknowledged.
     *
     * @param ModelInterface $model   the model used in the operation
     * @param array          $options possible options to send to mongo driver
     */
    public function delete(ModelInterface $model, array $options = []): bool
    {
        if (false === $this->fireEvent('deleting', $model, true)) {
            return false;
        }

        $queryResult = $model->getCollection()->deleteOne(
            ['_id' => $model->_id],
            $this->mergeOptions($options)
        );

        if ($queryResult->isAcknowledged() &&
            $queryResult->getDeletedCount()
        ) {
            $this->fireEvent('deleted', $model);

            return true;
        }

        return false;
    }

    /**
     * Retrieve a database cursor that will return models that upon iteration.
     *
     * @param ModelInterface $model      Model to query from collection
     * @param mixed          $query      MongoDB query to retrieve documents
     * @param array          $projection fields to project in MongoDB query
     * @param bool           $useCache   retrieves a CacheableCursor instead
     */
    public function where(ModelInterface $model, $query = [], array $projection = [], bool $useCache = false): CursorInterface
    {
        $cursor = $useCache ? CacheableCursor::class : Cursor::class;

        $query = Resolver::resolveQuery(
            $query,
            $model,
            $this->ignoreSoftDelete
        );
        return new $cursor(
            $model->getCollection(),
            'find',
            [
                $query,
                [
                    'projection' => $this->prepareProjection($projection),
                    'eagerLoads' => $model->with ?? [],
                ],
            ]
        );
    }

    /**
     * Retrieve a database cursor that will return all models upon iteration.
     *
     * @param ModelInterface $model Model to query from collection
     */
    public function all(ModelInterface $model): CursorInterface
    {
        return $this->where($model, []);
    }

    /**
     * Retrieve first model that matches given query.
     *
     * @param ModelInterface $model      Model to query from collection
     * @param mixed          $query      MongoDB query to retrieve the model
     * @param array          $projection fields to project in MongoDB query
     * @param boolean        $useCache   retrieves the first through a CacheableCursor
     *
     * @return ModelInterface|array|null
     */
    public function first(ModelInterface $model, $query = [], array $projection = [], bool $useCache = false)
    {
        if (null === $query) {
            return null;
        }

        if ($useCache) {
            return $this->where($model, $query, $projection, $useCache)->first();
        }

        $query = Resolver::resolveQuery(
            $query,
            $model,
        );

        return $model->getCollection()->findOne(
            $query,
            ['projection' => $this->prepareProjection($projection)],
        );
    }

    /**
     * Retrieve one model that matches given query.
     * If no model was found, throws an exception.
     *
     * @param ModelInterface $model      Model to query from collection
     * @param mixed          $query      MongoDB query to retrieve the model
     * @param array          $projection fields to project in MongoDB query
     * @param boolean        $useCache   retrieves the first through a CacheableCursor
     *
     * @throws ModelNotFoundException If no model was found
     *
     * @return ModelInterface|null
     */
    public function firstOrFail(ModelInterface $model, $query = [], array $projection = [], bool $useCache = false)
    {
        if ($result = $this->first($model, $query, $projection, $useCache)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel(get_class($model));
    }

    public function withoutSoftDelete(): self
    {
        $this->ignoreSoftDelete = true;

        return $this;
    }

    /**
     * Triggers an event. May return if that event had success.
     *
     * @param string $event identification of the event
     * @param mixed  $model event payload
     * @param bool   $halt  true if the return of the event handler will be used in a conditional
     *
     * @return mixed event handler return
     */
    protected function fireEvent(string $event, ModelInterface $model, bool $halt = false)
    {
        $event = "mongolid.{$event}: ".get_class($model);

        $this->eventService ?: $this->eventService = Container::make(EventTriggerService::class);

        return $this->eventService->fire($event, $model, $halt);
    }

    /**
     * Converts the given projection fields to Mongo driver format.
     *
     * How to use:
     *     As Mongo projection using boolean values:
     *         From: ['name' => true, '_id' => false]
     *         To:   ['name' => true, '_id' => false]
     *     As Mongo projection using integer values
     *         From: ['name' => 1, '_id' => -1]
     *         To:   ['name' => true, '_id' => false]
     *     As an array of string:
     *         From: ['name', '_id']
     *         To:   ['name' => true, '_id' => true]
     *     As an array of string to exclude some fields:
     *         From: ['name', '-_id']
     *         To:   ['name' => true, '_id' => false]
     *
     * @param array $fields fields to project
     *
     * @throws InvalidArgumentException If the given $fields are not a valid projection
     *
     * @return array
     */
    protected function prepareProjection(array $fields): array
    {
        $projection = [];
        foreach ($fields as $key => $value) {
            if (is_string($key)) {
                if (is_bool($value)) {
                    $projection[$key] = $value;

                    continue;
                }
                if (is_int($value)) {
                    $projection[$key] = ($value >= 1);

                    continue;
                }
            }

            if (is_int($key) && is_string($value)) {
                $key = $value;
                if (0 === strpos($value, '-')) {
                    $key = substr($key, 1);
                    $value = false;
                } else {
                    $value = true;
                }

                $projection[$key] = $value;

                continue;
            }

            throw new InvalidArgumentException(
                sprintf(
                    "Invalid projection: '%s' => '%s'",
                    $key,
                    $value
                )
            );
        }

        if ($projection) {
            $projection['__pclass'] = true;
        }

        return $projection;
    }

    /**
     * Based on the work of "bjori/mongo-php-transistor".
     * Calculate `$set` and `$unset` arrays for update operation and store them on $changes.
     *
     * @see https://github.com/bjori/mongo-php-transistor/blob/70f5af00795d67f4d5a8c397e831435814df9937/src/Transistor.php#L108
     */
    private function calculateChanges(array &$changes, array $newData, array $oldData, string $keyfix = ''): void
    {
        foreach ($newData as $k => $v) {
            if (!isset($oldData[$k])) { // new field
                $changes['$set']["{$keyfix}{$k}"] = $v;
            } elseif ($oldData[$k] != $v) {  // changed value
                if (is_array($v) && is_array($oldData[$k]) && $v) { // check array recursively for changes
                    $this->calculateChanges($changes, $v, $oldData[$k], "{$keyfix}{$k}.");
                } else {
                    // overwrite normal changes in keys
                    // this applies to previously empty arrays/documents too
                    $changes['$set']["{$keyfix}{$k}"] = $v;
                }
            }
        }

        foreach ($oldData as $k => $v) { // data that used to exist, but now doesn't
            if (!isset($newData[$k])) { // removed field
                $changes['$unset']["{$keyfix}{$k}"] = '';
                continue;
            }
        }
    }

    /**
     * Merge all options.
     *
     * @param array $defaultOptions default options array
     * @param array $toMergeOptions to merge options array
     *
     * @return array
     */
    private function mergeOptions(array $defaultOptions = [], array $toMergeOptions = []): array
    {
        return array_merge($defaultOptions, $toMergeOptions);
    }

    /**
     * Perform actions on object before firing the after event.
     */
    private function afterSuccess(ModelInterface $model): void
    {
        $model->syncOriginalDocumentAttributes();
    }

    private function getUpdateData($model, array $data): array
    {
        $changes = [];
        $this->calculateChanges($changes, $data, $model->getOriginalDocumentAttributes());

        return $changes;
    }
}
