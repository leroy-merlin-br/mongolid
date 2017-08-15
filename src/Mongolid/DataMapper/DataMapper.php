<?php

namespace Mongolid\DataMapper;

use InvalidArgumentException;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CacheableCursor;
use Mongolid\Cursor\Cursor;
use Mongolid\Event\EventTriggerService;
use Mongolid\Exception\ModelNotFoundException;
use Mongolid\Model\AttributesAccessInterface;
use Mongolid\Schema\HasSchemaInterface;
use Mongolid\Schema\Schema;
use Mongolid\Util\ObjectIdUtils;

/**
 * The DataMapper class will abstract how an Entity is persisted and retrieved
 * from the database.
 * The DataMapper will always use a Schema trough the SchemaMapper to parse the
 * document in and out of the database.
 */
class DataMapper implements HasSchemaInterface
{
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
     * @var Pool
     */
    protected $connPool;

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
     * @param Pool $connPool the connections that are going to be used to interact with the database
     */
    public function __construct(Pool $connPool)
    {
        $this->connPool = $connPool;
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
        if ($this->fireEvent('saving', $entity, true) === false) {
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
        if ($fireEvents && $this->fireEvent('inserting', $entity, true) === false) {
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
        if ($this->fireEvent('updating', $entity, true) === false) {
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

        $queryResult = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data],
            $this->mergeOptions($options)
        );

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
        if ($this->fireEvent('deleting', $entity, true) === false) {
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
     * @param bool  $cacheable  retrieves a CacheableCursor instead
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function where(
        $query = [],
        array $projection = [],
        bool $cacheable = false
    ): Cursor {
        $cursorClass = $cacheable ? CacheableCursor::class : Cursor::class;

        $cursor = new $cursorClass(
            $this->schema,
            $this->getCollection(),
            'find',
            [
                $this->prepareValueQuery($query),
                ['projection' => $this->prepareProjection($projection)],
            ]
        );

        return $cursor;
    }

    /**
     * Retrieve a database cursor that will return all documents as
     * $this->schema->entityClass objects upon iteration.
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function all(): Cursor
    {
        return $this->where([]);
    }

    /**
     * Retrieve one $this->schema->entityClass objects that matches the given
     * query.
     *
     * @param mixed $query      mongoDB query to retrieve the document
     * @param array $projection fields to project in Mongo query
     * @param bool  $cacheable  retrieves the first through a CacheableCursor
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

        $document = $this->getCollection()->findOne(
            $this->prepareValueQuery($query),
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
     * @param bool  $cacheable  retrieves the first through a CacheableCursor
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
            $this->schema = Ioc::make($this->schemaClass);
        }

        return Ioc::makeWith(SchemaMapper::class, ['schema' => $this->schema]);
    }

    /**
     * Retrieves the Collection object.
     *
     * @return Collection
     */
    protected function getCollection(): Collection
    {
        $conn = $this->connPool->getConnection();
        $database = $conn->defaultDatabase;
        $collection = $this->schema->collection;

        return $conn->getRawConnection()->$database->$collection;
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
            $value['_id'] = new ObjectID($value['_id']);
        }

        if (isset($value['_id']) &&
            is_array($value['_id'])
        ) {
            $value['_id'] = $this->prepareArrayFieldOfQuery($value['_id']);
        }

        return $value;
    }

    /**
     * Prepares an embedded array of an query. It will convert string ObjectIDs
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
                        $value[$operator][$index] = new ObjectID($id);
                    }
                }
            }
        }

        return $value;
    }

    /**
     * Retrieves an EntityAssembler instance.
     *
     * @return EntityAssembler
     */
    protected function getAssembler()
    {
        if (!$this->assembler) {
            $this->assembler = Ioc::make(EntityAssembler::class);
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

        $this->eventService ? $this->eventService : $this->eventService = Ioc::make(EventTriggerService::class);

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
                if (strpos($value, '-') === 0) {
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
        if ($entity instanceof AttributesAccessInterface) {
            $entity->syncOriginalAttributes();
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
}
