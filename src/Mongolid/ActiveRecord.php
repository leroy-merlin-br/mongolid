<?php
namespace Mongolid;

use Mongolid\DataMapper\DataMapper;
use Mongolid\Container\Ioc;

/**
 * The Mongolid\ActiveRecord base class will ensure to enable your entity to
 * have methods to interact with the database. It means that 'save', 'insert',
 * 'update', 'where', 'first' and 'all' are available within every instance.
 * The Mongolid\Schema that describes the entity will be generated on the go
 * based on the properties bellow.
 *
 * @package  Mongolid
 */
abstract class ActiveRecord
{
    /**
     * Name of the collection where this kind of Entity is going to be saved or
     * retrieved from
     * @var string
     */
    public $collection = 'mongolid';

    /**
     * Describes the Schema fields of the model. Optionally you can set it to
     * the name of a Schema class to be used.
     * @see  Mongolid\Schema::$fields
     * @var  string|string[]
     */
    protected $fields  = [
        '_id' => 'mongoId'
    ];

    /**
     * The $dynamic property tells if the object will accept additional fields
     * that are not specified in the $fields property. This is usefull if you
     * doesn't have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     * @var boolean
     */
    public $dynamic = true;

    /**
     * Saves this object into database
     *
     * @return boolean Success
     */
    public function save()
    {
        return $this->getDataMapper()->save($this);
    }

    /**
     * Insert this object into database
     *
     * @return boolean Success
     */
    public function insert()
    {
        return $this->getDataMapper()->insert($this);
    }

    /**
     * Updated this object in database
     *
     * @return boolean Success
     */
    public function update()
    {
        return $this->getDataMapper()->update($this);
    }

    /**
     * Gets a cursor of this kind of entities that matches the query from the
     * database
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function where($query)
    {
        return $this->getDataMapper()->where($query);
    }

    /**
     * Gets a cursor of this kind of entities from the database
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public function all()
    {
        return $this->getDataMapper()->all();
    }

    /**
     * Gets the first entity of this kind that matches the query
     *
     * @return ActiveRecord
     */
    public function first($query)
    {
        return $this->getDataMapper()->first($query);
    }

    /**
     * Returns a Schema object that describes this Entity in MongoDB
     *
     * @return Schema
     */
    protected function getSchema()
    {
        if (is_string($this->fields)) {
            return Ioc::make($this->fields);
        }

        $schema = new DynamicSchema;
        $schema->entityClass = get_class($this);
        $schema->fields      = $this->fields;
        $schema->dynamic     = $this->dynamic;

        return $schema;
    }

    /**
     * Returns a DataMapper configured with the Schema and collection described
     * in this entity
     *
     * @return DataMapper
     */
    protected function getDataMapper()
    {
        $dataMapper = new DataMapper;
        $dataMapper->collection = $this->collection;
        $dataMapper->schema     = $this->getSchema();

        return $dataMapper;
    }
}
