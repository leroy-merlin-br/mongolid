<?php

namespace Mongolid\DataMapper;

use InvalidArgumentException;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Cursor\EagerLoadedCursor;
use Mongolid\Cursor\SchemaCacheableCursor;
use Mongolid\Cursor\SchemaCursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\ModelInterface;
use Mongolid\Query\Resolver;
use Mongolid\Schema\HasSchemaInterface;
use Mongolid\Schema\Schema;

/**
 * The DataMapper class will abstract how an Entity is persisted and retrieved
 * from the database.
 * The DataMapper will always use a Schema trough the SchemaMapper to parse the
 * document in and out of the database.
 */
class DataMapper implements HasSchemaInterface
{
    private bool $ignoreSoftDelete = false;

    /**
     * Name of the schema class to be used.
     *
     * @var string
     */
    public $schemaClass = Schema::class;

    /**
     * Schema object. Will be set after the $schemaClass.
     *
     * @var Schema
     */
    protected $schema;

    /**
     * Connections that are going to be used to interact with the database.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Have the responsibility of assembling the data coming from the database into actual entities.
     *
     * @var EntityAssembler
     */
    protected $assembler;

    /**
     * In order to dispatch events when necessary.
     *
     * @var EventTriggerService
     */
    protected $eventService;

    /**
     * @var array
     */
    private $pullNullValues = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Upserts the given object into database. Returns success if write concern
     * is acknowledged.
     *
     * Notice: Saves with Unacknowledged WriteConcern will not fire `saved` event.
     *
     * @param mixed $entity  the entity used in the operation
     * @param array $options possible options to send to mongo driver
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function save($entity, array $options = [])
    {
        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This gives an opportunities to
        // listeners to cancel save operations if validations fail or whatever.
        if (false === $this->fireEvent('saving', $entity, true)) {
            return false;
        }

        $data = $this->parseToDocument($entity);

        $queryResult = $this->getCollection()->replaceOne(
            ['_id' => $data['_id']],
            $data,
            $this->mergeOptions($options, ['upsert' => true])
        );

        $result = $queryResult->isAcknowledged() &&
            ($queryResult->getModifiedCount() || $queryResult->getUpsertedCount());

        if ($result) {
            $this->afterSuccess($entity);

            $this->fireEvent('saved', $entity);
        }

        return $result;
    }

    /**
     * Inserts the given object into database. Returns success if write concern
     * is acknowledged. Since it's an insert, it will fail if the _id already
     * exists.
     *
     * Notice: Inserts with Unacknowledged WriteConcern will not fire `inserted` event.
     *
     * @param mixed $entity     the entity used in the operation
     * @param array $options    possible options to send to mongo driver
     * @param bool  $fireEvents whether events should be fired
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function insert($entity, array $options = [], bool $fireEvents = true): bool
    {
        if ($fireEvents && false === $this->fireEvent('inserting', $entity, true)) {
            return false;
        }

        $data = $this->parseToDocument($entity);

        $queryResult = $this->getCollection()->insertOne(
            $data,
            $this->mergeOptions($options)
        );

        $result = $queryResult->isAcknowledged() && $queryResult->getInsertedCount();

        if ($result) {
            $this->afterSuccess($entity);

            if ($fireEvents) {
                $this->fireEvent('inserted', $entity);
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
     *
     * @param mixed $entity  the entity used in the operation
     * @param array $options possible options to send to mongo driver
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function update($entity, array $options = []): bool
    {
        if (false === $this->fireEvent('updating', $entity, true)) {
            return false;
        }

        if (!$entity->_id) {
            if ($result = $this->insert($entity, $options, false)) {
                $this->afterSuccess($entity);

                $this->fireEvent('updated', $entity);
            }

            return $result;
        }

        $data = $this->parseToDocument($entity);

        if (!$updateData = $this->getUpdateData($entity, $data)) {
            return true;
        }

        $collection = $this->getCollection();
        $filter = ['_id' => $data['_id']];
        $updateOptions = $this->mergeOptions($options);

        $queryResult = $collection->updateOne($filter, $updateData, $updateOptions);

        if ($this->pullNullValues) {
            $collection->updateOne(
                $filter,
                ['$pull' => $this->pullNullValues],
                $updateOptions
            );
        }

        $result = $queryResult->isAcknowledged() && $queryResult->getModifiedCount();

        if ($result) {
            $this->afterSuccess($entity);

            $this->fireEvent('updated', $entity);
        }

        return $result;
    }

    /**
     * Removes the given document from the collection.
     *
     * Notice: Deletes with Unacknowledged WriteConcern will not fire `deleted` event.
     *
     * @param mixed $entity  the entity used in the operation
     * @param array $options possible options to send to mongo driver
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function delete($entity, array $options = []): bool
    {
        if (false === $this->fireEvent('deleting', $entity, true)) {
            return false;
        }

        $data = $this->parseToDocument($entity);

        $queryResult = $this->getCollection()->deleteOne(
            ['_id' => $data['_id']],
            $this->mergeOptions($options)
        );

        if ($queryResult->isAcknowledged() &&
            $queryResult->getDeletedCount()
        ) {
            $this->fireEvent('deleted', $entity);

            return true;
        }

        return false;
    }

    /**
     * Retrieve a database cursor that will return $this->schema->entityClass
     * objects that upon iteration.
     *
     * @param mixed $query      mongoDB query to retrieve documents
     * @param array $projection fields to project in Mongo query
     * @param bool  $cacheable  retrieves a SchemaCacheableCursor instead
     */
    public function where(
        $query = [],
        array $projection = [],
        bool $cacheable = false
    ): CursorInterface {
        $cursorClass = $cacheable ? SchemaCacheableCursor::class : SchemaCursor::class;

        $model = new $this->schema->entityClass;

        $query = Resolver::resolveQuery(
            $query,
            $model,
            $this->ignoreSoftDelete
        );

        return new $cursorClass(
            $this->schema,
            $this->getCollection(),
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
     * Retrieve a database cursor that will return all documents as
     * $this->schema->entityClass objects upon iteration.
     */
    public function all(): CursorInterface
    {
        return $this->where([]);
    }

    /**
     * Retrieve one $this->schema->entityClass objects that matches the given
     * query.
     *
     * @param mixed $query      mongoDB query to retrieve the document
     * @param array $projection fields to project in Mongo query
     * @param bool  $cacheable  retrieves the first through a SchemaCacheableCursor
     *
     * @return mixed First document matching query as an $this->schema->entityClass object
     */
    public function first(
        $query = [],
        array $projection = [],
        bool $cacheable = false
    ) {
        if ($cacheable) {
            return $this->where($query, $projection, true)->first();
        }

        $model = new $this->schema->entityClass;

        $query = Resolver::resolveQuery(
            $query,
            $model,
        );

        $document = $this->getCollection()->findOne(
            $query,
            ['projection' => $this->prepareProjection($projection)]
        );

        if (!$document) {
            return;
        }

        $model = $this->getAssembler()->assemble($document, $this->schema);

        return $model;
    }

    /**
     * Retrieve one $this->schema->entityClass objects that matches the given
     * query. If no document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB query to retrieve the document
     * @param array $projection fields to project in Mongo query
     * @param bool  $cacheable  retrieves the first through a SchemaCacheableCursor
     *
     * @throws ModelNotFoundException if no document was found
     *
     * @return mixed First document matching query as an $this->schema->entityClass object
     */
    public function firstOrFail(
        $query = [],
        array $projection = [],
        bool $cacheable = false
    ) {
        if ($result = $this->first($query, $projection, $cacheable)) {
            return $result;
        }

        throw (new ModelNotFoundException())->setModel($this->schema->entityClass);
    }

    public function withoutSoftDelete(): self
    {
        $this->ignoreSoftDelete = true;

        return $this;
    }

    /**
     * Parses an object with SchemaMapper and the given Schema.
     *
     * @param mixed $entity the object to be parsed
     *
     * @return array Document
     */
    protected function parseToDocument($entity)
    {
        $schemaMapper = $this->getSchemaMapper();
        $parsedDocument = $schemaMapper->map($entity);

        if (is_object($entity)) {
            foreach ($parsedDocument as $field => $value) {
                $entity->$field = $value;
            }
        }

        return $parsedDocument;
    }

    /**
     * Returns a SchemaMapper with the $schema or $schemaClass instance.
     *
     * @return SchemaMapper
     */
    protected function getSchemaMapper()
    {
        if (!$this->schema) {
            $this->schema = Container::make($this->schemaClass);
        }

        return Container::make(SchemaMapper::class, ['schema' => $this->schema]);
    }

    /**
     * Retrieves the Collection object.
     */
    public function getCollection(): Collection
    {
        $database = $this->connection->defaultDatabase;
        $collectionName = $this->schema->collection;
        $options = [
            'typeMap' => ['array' => 'array', 'document' => 'array']
        ];

        $collection = $this->connection
            ->getClient()
            ->selectDatabase($database, $options)
            ->selectCollection($collectionName);

        return $collection;
    }

    /**
     * Retrieves an EntityAssembler instance.
     *
     * @return EntityAssembler
     */
    protected function getAssembler()
    {
        if (!$this->assembler) {
            $this->assembler = Container::make(EntityAssembler::class);
        }

        return $this->assembler;
    }

    /**
     * Triggers an event. May return if that event had success.
     *
     * @param string $event  identification of the event
     * @param mixed  $entity event payload
     * @param bool   $halt   true if the return of the event handler will be used in a conditional
     *
     * @return mixed event handler return
     */
    protected function fireEvent(string $event, $entity, bool $halt = false)
    {
        $event = "mongolid.{$event}: ".get_class($entity);

        $this->eventService ? $this->eventService : $this->eventService = Container::make(EventTriggerService::class);

        return $this->eventService->fire($event, $entity, $halt);
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
     * @throws InvalidArgumentException if the given $fields are not a valid projection
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
     * Merge all options.
     *
     * @param array $defaultOptions default options array
     * @param array $toMergeOptions to merge options array
     *
     * @return array
     */
    private function mergeOptions(array $defaultOptions = [], array $toMergeOptions = [])
    {
        return array_merge($defaultOptions, $toMergeOptions);
    }

    /**
     * Perform actions on object before firing the after event.
     *
     * @param mixed $entity
     */
    private function afterSuccess($entity)
    {
        if ($entity instanceof ModelInterface) {
            $entity->syncOriginalDocumentAttributes();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * Set a Schema object  that describes an Entity in MongoDB.
     *
     * @param Schema $schema
     */
    public function setSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    private function getUpdateData($model, array $data): array
    {
        $this->pullNullValues = [];
        $changes = [];
        $oldData = [];

        if ($model instanceof ModelInterface) {
            $oldData = $model->getOriginalDocumentAttributes();
        }

        $this->calculateChanges($changes, $data, $oldData);

        return $changes;
    }

    /**
     * Based on the work of "bjori/mongo-php-transistor".
     * Calculate `$set` and `$unset` arrays for update operation and store them on $changes.
     * We also have a workaround for SERVER-1014, running a $pull on other update after the $unset,
     * when needed.
     *
     * @see https://jira.mongodb.org/browse/SERVER-1014
     * @see https://github.com/bjori/mongo-php-transistor/blob/70f5af00795d67f4d5a8c397e831435814df9937/src/Transistor.php#L108
     */
    private function calculateChanges(array &$changes, array $newData, array $oldData, string $keyfix = '')
    {
        foreach ($newData as $k => $v) {
            if (is_null($v)) {
                continue;
            }

            if (!isset($oldData[$k])) { // new field
                $changes['$set']["{$keyfix}{$k}"] = $v;
            } elseif ($oldData[$k] != $v) {  // changed value
                if (is_array($v) && is_array($oldData[$k]) && $v && $oldData[$k] !== []) { // check array recursively for changes
                    $this->calculateChanges($changes, $v, $oldData[$k], "{$keyfix}{$k}.");
                } else {
                    if (is_array($v)) {
                        $v = $this->filterNullValues($v);
                    }

                    // overwrite normal changes in keys
                    // this applies to previously empty arrays/documents too
                    $changes['$set']["{$keyfix}{$k}"] = $v;
                }
            }
        }

        foreach ($oldData as $k => $v) { // data that used to exist, but now doesn't
            if (!isset($newData[$k])) { // removed field
                if (is_integer($k)) {
                    $this->pullNullValues[rtrim($keyfix, '.')] = null;
                }
                $changes['$unset']["{$keyfix}{$k}"] = '';
                continue;
            }
        }
    }

    private function filterNullValues(array $data): array
    {
        $filtered =  array_filter(
            $data,
            function ($value) {
                return !is_null($value);
            }
        );

        if ($data == array_values($data)) {
            $filtered = array_values($filtered);
        }

        return $filtered;
    }
}
