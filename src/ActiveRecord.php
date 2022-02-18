<?php

namespace Mongolid;

use BadMethodCallException;
use MongoDB\Driver\WriteConcern;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Exception\NoCollectionNameException;
use Mongolid\Model\AbstractModel;
use Mongolid\Model\HasAttributesInterface;
use Mongolid\Model\HasAttributesTrait;
use Mongolid\Model\HasRelationsTrait;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\HasSchemaInterface;
use Mongolid\Schema\Schema;

/**
 * The Mongolid\ActiveRecord base class will ensure to enable your entity to
 * have methods to interact with the database. It means that 'save', 'insert',
 * 'update', 'where', 'first' and 'all' are available within every instance.
 * The Mongolid\Schema\Schema that describes the entity will be generated on the go
 * based on the $fields.
 */
abstract class ActiveRecord extends AbstractModel implements HasAttributesInterface, HasSchemaInterface
{
    use HasAttributesTrait, HasRelationsTrait;

    /**
     * Name of the collection where this kind of Entity is going to be saved or
     * retrieved from.
     *
     * @var string
     */
    protected $collection = null;

    /**
     * @see https://docs.mongodb.com/manual/reference/write-concern/
     *
     * @var int
     */
    protected $writeConcern = 1;

    /**
     * Describes the Schema fields of the model. Optionally you can set it to
     * the name of a Schema class to be used.
     *
     * @see  \Mongolid\Schema\Schema::$fields
     *
     * @var string|string[]
     */
    protected $fields = [
        '_id' => 'objectId',
        'created_at' => 'createdAtTimestamp',
        'updated_at' => 'updatedAtTimestamp',
    ];

    /**
     * The $dynamic property tells if the object will accept additional fields
     * that are not specified in the $fields property. This is useful if you
     * does not have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     *
     * @var bool
     */
    public $dynamic = true;

    /**
     * Handle dynamic method calls into the model.
     *
     * @param mixed $method     name of the method that is being called
     * @param mixed $parameters parameters of $method
     *
     * @throws BadMethodCallException in case of invalid methods be called
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $value = $parameters[0] ?? null;

        // Alias to attach
        if ('attachTo' == substr($method, 0, 8)) {
            $field = lcfirst(substr($method, 8));

            return $this->attach($field, $value);
        }

        // Alias to embed
        if ('embedTo' == substr($method, 0, 7)) {
            $field = lcfirst(substr($method, 7));

            return $this->embed($field, $value);
        }

        throw new BadMethodCallException(
            sprintf(
                'The following method can not be reached or does not exist: %s@%s',
                get_class($this),
                $method
            )
        );
    }

    /**
     * Returns a DataMapper configured with the Schema and collection described
     * in this entity.
     *
     * @return DataMapper
     */
    public function getDataMapper()
    {
        $dataMapper = Container::make(DataMapper::class);
        $dataMapper->setSchema($this->getSchema());

        return $dataMapper;
    }

    /**
     * Getter for the $collection attribute.
     *
     * @return string
     */
    public function getCollectionName(): ?string
    {
        return $this->collection ?: $this->getSchema()->collection;
    }


    /**
     * {@inheritdoc}
     */
    public function getSchema(): Schema
    {
        if ($schema = $this->instantiateSchemaInFields()) {
            return $schema;
        }

        $schema = new DynamicSchema();
        $schema->entityClass = get_class($this);
        $schema->fields = $this->fields;
        $schema->dynamic = $this->dynamic;
        $schema->collection = $this->collection;

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
            if (is_subclass_of($instance = Container::make($this->fields), Schema::class)) {
                return $instance;
            }
        }
    }
}
