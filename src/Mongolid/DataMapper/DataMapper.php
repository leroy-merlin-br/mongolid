<?php

namespace Mongolid\DataMapper;

use MongoDB\BSON\ObjectID;
use MongoDB\Collection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\Cursor;
use Mongolid\DataMapper\EntityAssembler;
use Mongolid\DataMapper\SchemaMapper;
use Mongolid\Event\EventTriggerService;
use Mongolid\Schema;

/**
 * The DataMapper class will abstract how an Entity is persisted and retrieved
 * from the database.
 * The DataMapper will always use a Schema trought the SchemaMapper to parse the
 * document in and out of the database.
 *
 * @package  Mongolid
 */
class DataMapper
{
    /**
     * Name of the schema class to be used
     * @var string
     */
    public $schemaClass = Schema::class;

    /**
     * Schema object. Will be set after the $schemaClass
     * @var Schema
     */
    public $schema;

    /**
     * Connections that are going to be used to interact with the database
     * @var Pool
     */
    protected $connPool;

    /**
     * Have the responsability of assembling the data coming from the database into actual entities.
     * @var EntityAssembler
     */
    protected $assembler;

    /**
     * In order to dispatch events when necessary
     * @var EventTriggerService
     */
    protected $eventService;

    /**
     * @param Pool $connPool The connections that are going to be used to interact with the database.
     */
    public function __construct(Pool $connPool)
    {
        $this->connPool  = $connPool;
    }

    /**
     * Upserts the given object into database. Returns success if write concern
     * is acknowledged.
     *
     * @param  mixed $object The object used in the operation.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function save($object)
    {
        // If the "saving" event returns false we'll bail out of the save and return
        // false, indicating that the save failed. This gives an opportunities to
        // listeners to cancel save operations if validations fail or whatever.
        if ($this->fireEvent('saving', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data],
            ['upsert' => true]
        );

        $result = (bool) ($result->getModifiedCount() + $result->getUpsertedCount());

        if ($result) {
            $this->fireEvent('saved', $object);
        }

        return $result;
    }

    /**
     * Inserts the given object into database. Returns success if write concern
     * is acknowledged. Since it's an insert, it will fail if the _id already
     * exists.
     *
     * @param  mixed $object The object used in the operation.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function insert($object): bool
    {
        if ($this->fireEvent('inserting', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->insertOne(
            $data
        );

        $result = (bool) $result->getInsertedCount();

        if ($result) {
            $this->fireEvent('inserted', $object);
        }

        return $result;
    }

    /**
     * Updates the given object into database. Returns success if write concern
     * is acknowledged. Since it's an update, it will fail if the document with
     * the given _id didn't exists.
     *
     * @param  mixed $object The object used in the operation.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function update($object)
    {
        if ($this->fireEvent('updating', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data]
        );

        $result = (bool) $result->getModifiedCount();

        if ($result) {
            $this->fireEvent('updated', $object);
        }

        return $result;
    }

    /**
     * Removes the given document from the collection.
     *
     * @param  mixed $object The object used in the operation.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function delete($object)
    {
        if ($this->fireEvent('deleting', $object, true) === false) {
            return false;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->deleteOne(
            ['_id' => $data['_id']]
        );

        $result = (bool) $result->getDeletedCount();

        if ($result) {
            $this->fireEvent('deleted', $object);
        }

        return $result;
    }

    /**
     * Retrieve a database cursor that will return $this->schema->entityClass
     * objects that upon iteration
     *
     * @param  mixed $query MongoDB query to retrieve documents.
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function where($query = []): Cursor
    {
        $cursor = new Cursor(
            $this->schema,
            $this->getCollection(),
            'find',
            [$this->prepareValueQuery($query)]
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
     * @param  mixed $query MongoDB query to retrieve the document.
     *
     * @return mixed First document matching query as an $this->schema->entityClass object
     */
    public function first($query = [])
    {
        $document = $this->getCollection()->findOne(
            $this->prepareValueQuery($query)
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
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && strlen($value) == 24 && ctype_xdigit($value)) {
            $value = new ObjectID($value);
        }

        return ['_id' => $value];
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
     * @param  boolean $halt   True if the return of the event handler will be used in a coditional.
     *
     * @return mixed            Event handler return.
     */
    protected function fireEvent(string $event, $entity, bool $halt = false)
    {
        $event = "mongolid.{$event}." . get_class($entity);

        $this->eventService ? $this->eventService : $this->eventService = Ioc::make(EventTriggerService::class);

        return $this->eventService->fire($event, $entity, $halt);
    }
}
