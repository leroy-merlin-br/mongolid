<?php
namespace Mongolid\DataMapper;

use InvalidArgumentException;
use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use MongoDB\DeleteResult;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CacheableCursor;
use Mongolid\Cursor\Cursor;
use Mongolid\DataMapper\EntityAssembler;
use Mongolid\DataMapper\SchemaMapper;
use Mongolid\Event\EventTriggerService;
use Mongolid\Schema;
use Mongolid\Util\ObjectIdUtils;

/**
 * The DataMapper class will abstract how an Entity is persisted and retrieved
 * from the database.
 * The DataMapper will always use a Schema trough the SchemaMapper to parse the
 * document in and out of the database.
 *
 * @package  Mongolid
 */
class DataMapper
{
    /**
     * Name of the schema class to be used
     *
     * @var string
     */
    public $schemaClass = Schema::class;

    /**
     * Schema object. Will be set after the $schemaClass
     *
     * @var Schema
     */
    public $schema;

    /**
     * Connections that are going to be used to interact with the database
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
     * In order to dispatch events when necessary
     *
     * @var EventTriggerService
     */
    protected $eventService;

    /**
     * @param Pool $connPool The connections that are going to be used to interact with the database.
     */
    public function __construct(Pool $connPool)
    {
        $this->connPool = $connPool;
    }

    /**
     * If $queryResult is acknowledged, then fire given event.
     *
     * @see $this->fire()
     *
     * @param InsertOneResult|UpdateResult|DeleteResult $queryResult
     * @param array                                     $fireArguments
     *
     * @return bool whether or not result was acknowledged
     */
    protected function fireEventIfAcknowledged($queryResult, ...$fireArguments)
    {
        if ($result = $queryResult->isAcknowledged()) {
            $this->fireEvent(...$fireArguments);
        }

        return $result;
    }

    /**
     * Upserts the given object into database. Returns success if write concern
     * is acknowledged.
     *
     * Notice: Saves with Unacknowledged WriteConcern will not fire `saved` event.
     *
     * @param  mixed $object  The object used in the operation.
     * @param  array $options Possible options to send to mongo driver.
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function save($object, array $options = [])
    {
        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This gives an opportunities to
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireEvent('saving', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $queryResult = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data],
            $this->mergeOptions($options, ['upsert' => true])
        );

        return $this->fireEventIfAcknowledged($queryResult, 'saved', $object);
    }

    /**
     * Inserts the given object into database. Returns success if write concern
     * is acknowledged. Since it's an insert, it will fail if the _id already
     * exists.
     *
     * Notice: Inserts with Unacknowledged WriteConcern will not fire `inserted` event.
     *
     * @param  mixed $object  The object used in the operation.
     *
     * @param  array $options Possible options to send to mongo driver.
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function insert($object, array $options = []): bool
    {
        if ($this->fireEvent('inserting', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $queryResult = $this->getCollection()->insertOne(
            $data,
            $this->mergeOptions($options)
        );

        return $this->fireEventIfAcknowledged($queryResult, 'inserted', $object);
    }

    /**
     * Updates the given object into database. Returns success if write concern
     * is acknowledged. Since it's an update, it will fail if the document with
     * the given _id did not exists.
     *
     * Notice: Updates with Unacknowledged WriteConcern will not fire `updated` event.
     *
     * @param  mixed $object  The object used in the operation.
     * @param  array $options Possible options to send to mongo driver.
     *
     * @return bool Success (but always false if write concern is Unacknowledged)
     */
    public function update($object, array $options = []): bool
    {
        if ($this->fireEvent('updating', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $queryResult = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data],
            $this->mergeOptions($options)
        );

        return $this->fireEventIfAcknowledged($queryResult, 'updated', $object);
    }

    /**
     * Removes the given document from the collection.
     *
     * Notice: Deletes with Unacknowledged WriteConcern will not fire `deleted` event.
     *
     * @param  mixed $object  The object used in the operation.
     * @param  array $options Possible options to send to mongo driver.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function delete($object, array $options = []): bool
    {
        if ($this->fireEvent('deleting', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $queryResult = $this->getCollection()->deleteOne(
            ['_id' => $data['_id']],
            $this->mergeOptions($options)
        );

        return $this->fireEventIfAcknowledged($queryResult, 'deleted', $object);
    }

    /**
     * Retrieve a database cursor that will return $this->schema->entityClass
     * objects that upon iteration
     *
     * @param  mixed   $query      MongoDB query to retrieve documents.
     * @param  array   $projection Fields to project in Mongo query.
     * @param  boolean $cacheable  Retrieves a CacheableCursor instead.
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
                ['projection' => $this->prepareProjection($projection)]
            ]
        );

        return $cursor;
    }

    /**
     * Retrieve a database cursor that will return all documents as
     * $this->schema->entityClass objects upon iteration
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function all(): Cursor
    {
        return $this->where([]);
    }

    /**
     * Retrieve one $this->schema->entityClass objects that matches the given
     * query
     *
     * @param  mixed   $query      MongoDB query to retrieve the document.
     * @param  array   $projection Fields to project in Mongo query.
     * @param  boolean $cacheable  Retrieves the first through a CacheableCursor.
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

        if (! $document) {
            return null;
        }

        $model = $this->getAssembler()->assemble($document, $this->schema);

        return $model;
    }

    /**
     * Parses an object with SchemaMapper and the given Schema
     *
     * @param  mixed $object The object to be parsed.
     *
     * @return array  Document
     */
    protected function parseToDocument($object)
    {
        $schemaMapper   = $this->getSchemaMapper();
        $parsedDocument = $schemaMapper->map($object);

        if (is_object($object)) {
            foreach ($parsedDocument as $field => $value) {
                $object->$field = $value;
            }
        }

        return $parsedDocument;
    }

    /**
     * Returns a SchemaMapper with the $schema or $schemaClass instance
     *
     * @return SchemaMapper
     */
    protected function getSchemaMapper()
    {
        if (! $this->schema) {
            $this->schema = Ioc::make($this->schemaClass);
        }

        return Ioc::make(SchemaMapper::class, [$this->schema]);
    }

    /**
     * Retrieves the Collection object.
     *
     * @return Collection
     */
    protected function getCollection(): Collection
    {
        $conn       = $this->connPool->getConnection();
        $database   = $conn->defaultDatabase;
        $collection = $this->schema->collection;

        return $conn->getRawConnection()->$database->$collection;
    }

    /**
     * Transforms a value that is not an array into an MongoDB query (array).
     * This method will take care of converting a single value into a query for
     * an _id, including when a objectId is passed as a string.
     *
     * @param  mixed $value The _id of the document.
     *
     * @return array Query for the given _id
     */
    protected function prepareValueQuery($value): array
    {
        if (! is_array($value)) {
            $value = ['_id' => $value];
        }

        if (isset($value['_id']) &&
            is_string($value['_id']) &&
            ObjectIdUtils::isObjectId($value['_id'])
        ) {
            $value['_id'] = new ObjectID($value['_id']);
        }

        return $value;
    }

    /**
     * Retrieves an EntityAssembler instance
     *
     * @return EntityAssembler
     */
    protected function getAssembler()
    {
        if (! $this->assembler) {
            $this->assembler = Ioc::make(EntityAssembler::class);
        }

        return $this->assembler;
    }

    /**
     * Triggers an event. May return if that event had success.
     *
     * @param  string  $event  Identification of the event.
     * @param  mixed   $entity Event payload.
     * @param  boolean $halt   True if the return of the event handler will be used in a conditional.
     *
     * @return mixed            Event handler return.
     */
    protected function fireEvent(string $event, $entity, bool $halt = false)
    {
        $event = "mongolid.{$event}." . get_class($entity);

        $this->eventService ? $this->eventService : $this->eventService = Ioc::make(EventTriggerService::class);

        return $this->eventService->fire($event, $entity, $halt);
    }

    /**
     * Converts the given projection fields to Mongo driver format
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
     * @param  array  $fields Fields to project.
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
                if (is_integer($value)) {
                    $projection[$key] = ($value >= 1);
                    continue;
                }
            }

            if (is_integer($key) && is_string($value)) {
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

            throw new InvalidArgumentException(sprintf(
                "Invalid projection: '%s' => '%s'",
                $key,
                $value
            ));
        }

        return $projection;
    }

    /**
     * Merge all options.
     *
     * @param array $defaultOptions Default options array.
     * @param array $toMergeOptions To merge options array.
     *
     * @return array
     */
    private function mergeOptions(array $defaultOptions = [], array $toMergeOptions = [])
    {
        return array_merge($defaultOptions, $toMergeOptions);
    }
}
