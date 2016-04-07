<?php
namespace Mongolid\DataMapper;

use Mongolid\Schema;
use Mongolid\Container\Ioc;
use Mongolid\Connection\Pool;
use Mongolid\Cursor\Cursor;
use MongoDB\Collection;
use MongoDB\BSON\ObjectID;

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
     * @param Pool $connPool The connections that are going to be used to interact with the database.
     */
    public function __construct(Pool $connPool)
    {
        $this->connPool = $connPool;
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
        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data],
            ['upsert' => true]
        );

        return (bool) ($result->getModifiedCount() + $result->getUpsertedCount());
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
        if (! $object->_id) {
            $object->_id = new ObjectID;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->insertOne(
            $data
        );

        return (bool) $result->getInsertedCount();
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
        if (! $object->_id) {
            $object->_id = new ObjectID;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            ['$set' => $data]
        );

        return (bool) $result->getModifiedCount();
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
        if (! $object->_id) {
            $object->_id = new ObjectID;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->deleteOne(
            ['_id' => $data['_id']]
        );

        return (bool) $result->getDeletedCount();
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
            $this->schema->entityClass,
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
    public function first($query)
    {
        $document = $this->getCollection()->findOne(
            $this->prepareValueQuery($query)
        );

        if (! $document) {
            return null;
        }

        $model = new $this->schema->entityClass;

        foreach ($document as $key => $value) {
            $model->$key = $value;
        }

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
        $schemaMapper     = $this->getSchemaMapper();
        $objectAttributes = $this->parseToArray($object);

        $parsedDocument = $schemaMapper->map($objectAttributes);

        if (isset($parsedDocument['_id']) && is_object($object)) {
            $object->_id = $parsedDocument['_id'];
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

        return Ioc::make('Mongolid\DataMapper\SchemaMapper', [$this->schema]);
    }

    /**
     * Parses an object to an array before sending it to the SchemaMapper
     *
     * @param  mixed $object The object that will be transformed into an array.
     *
     * @return array
     */
    protected function parseToArray($object): array
    {
        if (! is_array($object)) {
            if (method_exists($object, 'getAttributes')) {
                return $object->getAttributes();
            }

            return get_object_vars($object);
        }

        return $object;
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
}
