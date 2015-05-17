<?php
namespace Mongolid;

use Mongolid\Container\Ioc;

class Model
{
    /**
     * Connection's object with MongoDB.
     * @var Mongolid\Mongolid\Connection\Connection
     */
    protected static $connection;

    /**
     * Indicate if the object is new or already persisted.
     * @var boolean
     */
    public $exists = false;

    /**
     * Current attributes.
     * @var array
     */
    public $attributes = [];

    /**
     * Original values at MongDB.
     * @var array
     */
    public $original = [];

    /**
     * Collection's name.
     * @var mixed
     */
    protected $collection = false;

    /**
     * Database's name.
     * @var string
     */
    protected $database = false;

    /**
     * Write Concern option.
     * @var integer
     */
    protected $writeConcern = 1;

    /**
     * Performs save action to persist into database.
     *
     * @return boolean
     */
    public function save()
    {
        $query = $this->newQueryBuilder();

        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        if (! $this->exists) {
            $saved = $query->save($this);
        } else {
            $saved = $query->update($this);
        }

        if ($saved) {
            $this->finishSave();
        }

        return $saved;
    }

    /**
     * Performs a update operation.
     * @return boolean
     */
    public function update()
    {
        $query = $this->newQueryBuilder();

        if (! $this->exists) {
            return $this->save();
        }

        if ($this->fireModelEvent('updating') === false) {
            return false;
        }

        return $query->update($this);
    }

    /**
     * Performs a insert operation into MongoDB.
     * @return boolean
     */
    public function insert()
    {
        $query = $this->newQueryBuilder();

        if (
            $this->fireModelEvent('saving')   === false ||
            $this->fireModelEvent('creating') === false
        ) {
            return false;
        }

        $result = $query->insert($this);

        if ($result) {
            $this->fireModelEvent('saved', false);
            $this->fireModelEvent('created', false);
        }

        return $result;
    }

    /**
     * Performs a delete operation into MongoDB.
     * @return boolean
     */
    public function delete()
    {
        $query = $this->newQueryBuilder();

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        $result = $query->delete($this);

        if ($result) {
            $this->fireModelEvent('deleted', false);
        }

        return $result;
    }


    /**
     * This method will can be overwritten in order to fire events to the
     * application. This gives an opportunities to implement the observer design
     * pattern.
     *
     * @param  string $eventName
     * @param  bool   $halt
     * @return mixed
     */
    public function fireModelEvent($eventName, $halt = true)
    {
        return true;
    }

    /**
     * Returns the collection's name
     * @return mixed
     */
    public function getCollectionName()
    {
        return $this->collection;
    }

    /**
     * Returns the database's name
     * @return mixed
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Returns the WriteConcern.
     * @return integer
     */
    public function getWriteConcern()
    {
        return $this->writeConcern;
    }

    /**
     * Finishes save() method execution.
     * @return null
     */
    public function finishSave()
    {
        $this->fireModelEvent('saved');

        $this->syncOriginal();
    }

    /**
     * Overwrites the current attributes as original
     * attributes retrieved at MongoDB.
     * @return null
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;
    }

    /**
     * Returns a new connection to MongoDB.
     * @return Mongolid\Mongolid\Connection\Connection
     */
    protected function getConnection()
    {

        if (! static::$connection) {
            $connector = Ioc::make('Mongolid\Connection\Connection');

            $connector->setDatabase($this->getDatabaseName());
            $connector->setCollection($this->getCollectionName());
            $connector->setWriteConcern($this->getWriteConcern());

            static::$connection = $connector;
        }

        return static::$connection;
    }

    /**
     * Instantiate a new Query Builder object.
     * @return Mongolid\Mongolid\Query\Builder
     */
    protected function newQueryBuilder()
    {
        $conn = $this->getConnection();

        return Ioc::make('Mongolid\Query\Builder', [$conn]);
    }

    /**
     * Fallback for method that are not into Model.
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->newQueryBuilder();

        return call_user_func_array([$query, $method], $parameters);
    }

    /**
     * Call method dynamically as static.
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;

        return call_user_func_array([$instance, $method], $parameters);
    }
}
