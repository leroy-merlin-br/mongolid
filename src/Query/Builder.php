<?php
namespace Mongolid\Query;

use InvalidArgumentException;
use MongoDB\BSON\ObjectId;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\Cursor;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\ModelInterface;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Util\ObjectIdUtils;

/**
 * This class will abstract how a Model is persisted and retrieved
 * from the database.
 * The Builder will always use a Schema trough the SchemaMapper to parse the
 * document in and out of the database.
 */
class Builder
{
    /**
     * Name of the schema class to be used.
     *
     * @var string
     */
    public $schemaClass = DynamicSchema::class;

    /**
     * Schema object. Will be set after the $schemaClass.
     *
     * @var DynamicSchema
     */
    protected $schema;

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

        // TODO rework this
        $model->bsonSerialize();

        $queryResult = $this->getCollection()->replaceOne(
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

        $queryResult = $this->getCollection()->insertOne(
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
     * is acknowledged. Since it's an update, it will fail if the document with
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

        // TODO review this
        $updateData = $this->getUpdateData($model, $model->bsonSerialize());

        $queryResult = $this->getCollection()->updateOne(
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

        $queryResult = $this->getCollection()->deleteOne(
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
     * Retrieve a database cursor that will return $this->schema->modelClass
     * objects that upon iteration.
     *
     * @param mixed $query      mongoDB query to retrieve documents
     * @param array $projection fields to project in Mongo query
     */
    public function where($query = [], array $projection = []): CursorInterface
    {
        return new Cursor(
            $this->schema,
            $this->getCollection(),
            'find',
            [
                $this->prepareValueQuery($query),
                ['projection' => $this->prepareProjection($projection)],
            ]
        );
    }

    /**
     * Retrieve a database cursor that will return all documents as
     * $this->schema->modelClass objects upon iteration.
     */
    public function all(): CursorInterface
    {
        return $this->where([]);
    }

    /**
     * Retrieve one $this->schema->modelClass objects that matches the given
     * query.
     *
     * @param mixed $query      mongoDB query to retrieve the document
     * @param array $projection fields to project in Mongo query
     *
     * @return static|null First document matching query as an $this->schema->modelClass object
     */
    public function first($query = [], array $projection = [])
    {
        if (null === $query) {
            return null;
        }

        return $this->getCollection()->findOne(
            $this->prepareValueQuery($query),
            ['projection' => $this->prepareProjection($projection)]
        );
    }

    /**
     * Retrieve one $this->schema->modelClass objects that matches the given
     * query. If no document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB query to retrieve the document
     * @param array $projection fields to project in Mongo query
     *
     * @throws ModelNotFoundException If no document was found
     *
     * @return static|null First document matching query as an $this->schema->modelClass object
     */
    public function firstOrFail($query = [], array $projection = [])
    {
        if ($result = $this->first($query, $projection)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel($this->schema->modelClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(): ?DynamicSchema
    {
        return $this->schema;
    }

    /**
     * Set a Schema object  that describes an Model in MongoDB.
     */
    public function setSchema(DynamicSchema $schema): void
    {
        $this->schema = $schema;
    }

    /**
     * Returns a SchemaMapper with the $schema or $schemaClass instance.
     */
    protected function getSchemaMapper(): SchemaMapper
    {
        if (!$this->schema) {
            $this->schema = Ioc::make($this->schemaClass);
        }

        return Ioc::make(SchemaMapper::class, ['schema' => $this->schema]);
    }

    /**
     * Retrieves the Collection object.
     */
    protected function getCollection(): Collection
    {
        $connection = $this->connection;
        $database = $connection->defaultDatabase;
        $collection = $this->getSchema()->collection;

        return $connection->getRawConnection()->$database->$collection;
    }

    /**
     * Transforms a value that is not an array into an MongoDB query (array).
     * This method will take care of converting a single value into a query for
     * an _id, including when a objectId is passed as a string.
     *
     * @param mixed $value the _id of the document
     *
     * @return array Query for the given _id
     */
    protected function prepareValueQuery($value): array
    {
        if (!is_array($value)) {
            $value = ['_id' => $value];
        }

        if (isset($value['_id']) &&
            is_string($value['_id']) &&
            ObjectIdUtils::isObjectId($value['_id'])
        ) {
            $value['_id'] = new ObjectId($value['_id']);
        }

        if (isset($value['_id']) &&
            is_array($value['_id'])
        ) {
            $value['_id'] = $this->prepareArrayFieldOfQuery($value['_id']);
        }

        return $value;
    }

    /**
     * Prepares an embedded array of an query. It will convert string ObjectIds
     * in operators into actual objects.
     *
     * @param array $value array that will be treated
     *
     * @return array prepared array
     */
    protected function prepareArrayFieldOfQuery(array $value): array
    {
        foreach (['$in', '$nin'] as $operator) {
            if (isset($value[$operator]) &&
                is_array($value[$operator])
            ) {
                foreach ($value[$operator] as $index => $id) {
                    if (ObjectIdUtils::isObjectId($id)) {
                        $value[$operator][$index] = new ObjectId($id);
                    }
                }
            }
        }

        return $value;
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

        $this->eventService ?: $this->eventService = Ioc::make(EventTriggerService::class);

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
    protected function prepareProjection(array $fields)
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

        return $projection;
    }

    /**
     * Based on the work of bjori/mongo-php-transistor.
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
                if (is_array($v) && $oldData[$k] && $v) { // check array recursively for changes
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
