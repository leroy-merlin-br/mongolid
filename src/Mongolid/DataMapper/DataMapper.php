<?php
namespace Mongolid\DataMapper;

use Mongolid\Schema;
use Mongolid\Container\Ioc;
use Mongolid\Connection\Pool;
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
     * @param Pool        $connPool The connections that are going to be used to interact with the database
     * @param Schema|null $schema   The Schema class of the model that is going go be persisted
     */
    public function __construct(Pool $connPool)
    {
        $this->connPool = $connPool;
    }

    /**
     * Upserts the given object into database. Returns success if write concern
     * is acknowledged.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function save($object)
    {
        if (! $object->_id) {
            $object->_id = new ObjectID;
        }

        $data = $this->parseToDocument($object);

        $result = $this->getCollection()->updateOne(
            ['_id' => $data['_id']],
            $data,
            ['upsert' => true]
        );

        return (bool) $result->getModifiedCount();
    }

    /**
     * Inserts the given object into database. Returns success if write concern
     * is acknowledged. Since it's an insert, it will fail if the _id already
     * exists.
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
            $data
        );

        return (bool) $result->getModifiedCount();
    }

    /**
     * Retrieve a database cursor that will return $this->schema->entityClass
     * objects that upon iteration
     *
     * @param  array $query MongoDB query to retrieve documents
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function where($query = [])
    {
        $rawCursor = $this->getCollection()->find(
            $query
        );

        return $rawCursor;
    }

    /**
     * Retrieve a database cursor that will return all documents as
     * $this->schema->entityClass objects upon iteration
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function all()
    {
        return $this->where([]);
    }

    /**
     * Retrieve one $this->schema->entityClass objects that matches the given
     * query
     *
     * @param  array $query MongoDB query to retrieve the document
     *
     * @return mixed First document matching query as an $this->schema->entityClass object
     */
    public function first($query)
    {
        $document = $this->getCollection()->findOne(
            $query
        );

        return $document;
    }

    /**
     * Parses an object with SchemaMapper and the given Schema
     *
     * @param  mixed  $object
     *
     * @return array  Document
     */
    protected function parseToDocument($object)
    {
        $schemaMapper = $this->getSchemaMapper();
        $object       = $this->parseToArray($object);

        return $schemaMapper->map($object);
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
     * @param  mixed $object
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
}
