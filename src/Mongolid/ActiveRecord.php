<?php
namespace Mongolid;

use BadMethodCallException;
use Mongolid\Container\Ioc;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Model\Attributes;
use Mongolid\Model\Relations;
use Mongolid\Schema;

/**
 * The Mongolid\ActiveRecord base class will ensure to enable your entity to
 * have methods to interact with the database. It means that 'save', 'insert',
 * 'update', 'where', 'first' and 'all' are available within every instance.
 * The Mongolid\Schema that describes the entity will be generated on the go
 * based on the $fields.
 *
 * @package  Mongolid
 */
abstract class ActiveRecord
{
    use Attributes, Relations;

    /**
     * Name of the collection where this kind of Entity is going to be saved or
     * retrieved from
     *
     * @var string
     */
    public $collection = 'mongolid';

    /**
     * Describes the Schema fields of the model. Optionally you can set it to
     * the name of a Schema class to be used.
     *
     * @see  Mongolid\Schema::$fields
     * @var  string|string[]
     */
    protected $fields  = [
        '_id' => 'objectId',
        'created_at' => 'createdAtTimestamp',
        'updated_at' => 'updatedAtTimestamp'
    ];

    /**
     * The $dynamic property tells if the object will accept additional fields
     * that are not specified in the $fields property. This is useful if you
     * does not have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     *
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
        return $this->execute('save');
    }

    /**
     * Insert this object into database
     *
     * @return boolean Success
     */
    public function insert()
    {
        return $this->execute('insert');
    }

    /**
     * Updates this object in database
     *
     * @return boolean Success
     */
    public function update()
    {
        return $this->execute('update');
    }

    /**
     * Deletes this object in database
     *
     * @return boolean Success
     */
    public function delete()
    {
        return $this->execute('delete');
    }

    /**
     * Gets a cursor of this kind of entities that matches the query from the
     * database
     *
     * @param  array $query MongoDB selection criteria.
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public static function where(array $query = [])
    {
        return Ioc::make(get_called_class())
            ->getDataMapper()->where($query);
    }

    /**
     * Gets a cursor of this kind of entities from the database
     *
     * @return \Mongolid\Cursor\Cursor
     */
    public static function all()
    {
        return Ioc::make(get_called_class())
            ->getDataMapper()->all();
    }

    /**
     * Gets the first entity of this kind that matches the query
     *
     * @param  mixed $query MongoDB selection criteria.
     *
     * @return ActiveRecord
     */
    public static function first($query = [])
    {
        return Ioc::make(get_called_class())
            ->getDataMapper()->first($query);
    }

    /**
     * Handle dynamic method calls into the model.
     * @codeCoverageIgnore
     *
     * @param  mixed $method     Name of the method that is being called.
     * @param  mixed $parameters Parameters of $method.
     *
     * @throws BadMethodCallException In case of invalid methods be called.
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $value = $parameters[0] ?? null;

        // Alias to attach
        if ('attachTo' == substr($method, 0, 8)) {
            $field = strtolower(substr($method, 8));
            return $this->attach($field, $value);
        }

        // Alias to embed
        if ('embedTo' == substr($method, 0, 7)) {
            $field = strtolower(substr($method, 7));
            return $this->embed($field, $value);
        }

        throw new BadMethodCallException(sprintf(
            'The following method can not be reached or does not exist: %s@%s',
            get_class($this),
            $method
        ));
    }

    /**
     * Returns a DataMapper configured with the Schema and collection described
     * in this entity
     *
     * @return DataMapper
     */
    public function getDataMapper()
    {
        $dataMapper = Ioc::make(DataMapper::class);
        $dataMapper->schema = $this->getSchema();

        return $dataMapper;
    }

    /**
     * Returns a Schema object that describes this Entity in MongoDB
     *
     * @return Schema
     */
    protected function getSchema(): Schema
    {
        if ($schema = $this->instantiateSchemaInFields()) {
            return $schema;
        }

        $schema = new DynamicSchema;
        $schema->entityClass = get_class($this);
        $schema->fields      = $this->fields;
        $schema->dynamic     = $this->dynamic;
        $schema->collection  = $this->collection;

        return $schema;
    }

    /**
     * Will check if the current value of $fields property is the name of a
     * Schema class and instantiate it if possible.
     *
     * @return Schema|null
     */
    protected function instantiateSchemaInFields()
    {
        if (is_string($this->fields)) {
            if (is_subclass_of($instance = Ioc::make($this->fields), Schema::class)) {
                return $instance;
            }
        }
    }

    /**
     * Performs the given action into database
     *
     * @param  string $action Datamapper function to execute.
     *
     * @return boolean
     */
    protected function execute(string $action)
    {
        if (! $this->collection) {
            return false;
        }

        return $this->getDataMapper()->$action($this);
    }

    /**
     * Getter for the $collection attribute.
     *
     * @return string
     */
    public function getCollectionName()
    {
        return $this->collection;
    }
}
