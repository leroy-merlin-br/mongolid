<?php
namespace Mongolid\DataMapper;

use Mongolid\Schema;
use Mongolid\Container\Ioc;

class DataMapper
{
    /**
     * Name of the collection where the object is going to be saved or retrieved
     * from
     * @var string
     */
    public $collection = 'mongolid';

    /**
     * Name of the schema class to be used
     * @var string
     */
    public $schemaClass = 'Mongolid\Schema';

    /**
     * Schema object. Will be set after the $schemaClass
     * @var Schema
     */
    public $schema;

    /**
     * Upserts the given object into database. Returns success if write concern
     * is acknowledged.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function save($object)
    {
        return $this->performCommand(
            'upsert',
            $this->collection,
            $this->parseToDocument($object)
        );
    }

    /**
     * Inserts the given object into database. Returns success if write concern
     * is acknowledged. Since it's an insert, it will fail if the _id already
     * exists.
     *
     * @return boolean Success (but always false if write concern is Unacknowledged)
     */
    public function insert($object)
    {
        return $this->performCommand(
            'insert',
            $this->collection,
            $this->parseToDocument($object)
        );
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
        return $this->performCommand(
            'update',
            $this->collection,
            $this->parseToDocument($object)
        );
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
        $rawCursor = $this->performCommand(
            'where',
            $this->collection,
            $query
        );

        return Ioc::make(
            'Mongolid\Cursor\Cursor',
            [$rawCursor, $this->getSchemaMapper()->entityClass]
        );
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
        return $this->where($query)->limit(1)->first();
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
    protected function parseToArray($object)
    {
        if (! is_array($object)) {
            if (method_exists($object, 'toArray')) {
                return $object->toArray();
            }

            return get_object_vars($object);
        }

        return $object;
    }
}
