<?php
namespace Mongolid\Model;

use MongoDB\Driver\WriteConcern;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Query\Builder;
use Mongolid\Query\SchemaMapper;
use Mongolid\Schema\AbstractSchema;
use Mongolid\Schema\DynamicSchema;

/**
 * The Mongolid\Model\Model base class will ensure to enable your model to
 * have methods to interact with the database. It means that 'save', 'insert',
 * 'update', 'where', 'first' and 'all' are available within every instance.
 * The Mongolid\Schema\Schema that describes the model will be generated on the go
 * based on the $fields.
 */
abstract class AbstractModel implements ModelInterface
{
    use HasAttributesTrait;
    use HasRelationsTrait;

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
     * Name of the collection where this kind of Model is going to be saved or
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
     * @see  \Mongolid\Schema\AbstractSchema::$fields
     *
     * @var string|string[]
     */
    protected $fields = [
        '_id' => 'objectId',
        'created_at' => 'createdAtTimestamp',
        'updated_at' => 'updatedAtTimestamp',
    ];

    /**
     * Gets a cursor of this kind of entities that matches the query from the
     * database.
     *
     * @param array $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     */
    public static function where(array $query = [], array $projection = []): CursorInterface
    {
        return self::getBuilderInstance()->where($query, $projection);
    }

    /**
     * Gets a cursor of this kind of entities from the database.
     */
    public static function all(): CursorInterface
    {
        return self::getBuilderInstance()->all();
    }

    /**
     * Gets the first model of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     */
    public static function first($query = [], array $projection = []): ?ModelInterface
    {
        return self::getBuilderInstance()->first($query, $projection);
    }

    /**
     * Gets the first model of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     *
     * @throws ModelNotFoundException If no document was found
     */
    public static function firstOrFail($query = [], array $projection = []): ?ModelInterface
    {
        return self::getBuilderInstance()->firstOrFail($query, $projection);
    }

    /**
     * Gets the first model of this kind that matches the query. If no
     * document was found, a new model will be returned with the
     * _if field filled.
     *
     * @param mixed $id document id
     */
    public static function firstOrNew($id): ?ModelInterface
    {
        if (!$model = self::getBuilderInstance()->first($id)) {
            $model = new static();
            $model->_id = $id;
        }

        return $model;
    }

    /**
     * Returns a valid instance from Ioc.
     *
     * @throws NoCollectionNameException Throws exception when has no collection filled
     */
    private static function getBuilderInstance(): Builder
    {
        $instance = new static();

        if (!$instance->getCollectionName()) {
            throw new NoCollectionNameException();
        }

        return $instance->getBuilder();
    }

    /**
     * Saves this object into database.
     */
    public function save(): bool
    {
        return $this->execute('save');
    }

    /**
     * Insert this object into database.
     */
    public function insert(): bool
    {
        return $this->execute('insert');
    }

    /**
     * Updates this object in database.
     */
    public function update(): bool
    {
        return $this->execute('update');
    }

    /**
     * Deletes this object in database.
     */
    public function delete(): bool
    {
        return $this->execute('delete');
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param string $key name of the attribute
     *
     * @return mixed
     */
    public function &__get(string $key)
    {
        return $this->getDocumentAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param string $key   attribute name
     * @param mixed  $value value to be set
     */
    public function __set(string $key, $value): void
    {
        $this->setDocumentAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param string $key attribute name
     */
    public function __isset(string $key): bool
    {
        return $this->hasDocumentAttribute($key);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key attribute name
     */
    public function __unset(string $key): void
    {
        $this->cleanDocumentAttribute($key);
    }

    /**
     * Returns a Builder configured with the Schema and collection described
     * in this model.
     */
    public function getBuilder(): Builder
    {
        $builder = Ioc::make(Builder::class);
        $builder->setSchema($this->getSchema());

        return $builder;
    }

    /**
     * Getter for the $collection attribute.
     */
    public function getCollectionName(): ?string
    {
        return $this->collection ?: $this->getSchema()->collection;
    }

    /**
     * Getter for $writeConcern attribute.
     */
    public function getWriteConcern(): int
    {
        return $this->writeConcern;
    }

    /**
     * Setter for $writeConcern attribute.
     *
     * @param int $writeConcern level of write concern for the transation
     */
    public function setWriteConcern(int $writeConcern): void
    {
        $this->writeConcern = $writeConcern;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(): AbstractSchema
    {
        if ($schema = $this->instantiateSchemaInFields()) {
            return $schema;
        }

        $schema = new DynamicSchema();
        $schema->modelClass = static::class;
        $schema->fields = $this->fields;
        $schema->dynamic = $this->dynamic;
        $schema->collection = $this->collection;

        return $schema;
    }

    public function bsonSerialize()
    {
        $schemaMapper = Ioc::make(SchemaMapper::class, ['schema' => $this->getSchema()]);

        $parsedDocument = $schemaMapper->map($this);

        foreach ($parsedDocument as $field => $value) {
            $this->setDocumentAttribute($field, $value);
        }

        return $parsedDocument;
    }

    public function bsonUnserialize(array $data)
    {
        unset($data['__pclass']);
        $this->fill($data);

        $this->syncOriginalDocumentAttributes();
    }

    /**
     * Will check if the current value of $fields property is the name of a
     * Schema class and instantiate it if possible.
     */
    protected function instantiateSchemaInFields(): ?AbstractSchema
    {
        if (is_string($this->fields)) {
            if (is_subclass_of($instance = Ioc::make($this->fields), AbstractSchema::class)) {
                return $instance;
            }
        }

        return null;
    }

    /**
     * Performs the given action into database.
     *
     * @param string $action Builder function to execute
     */
    protected function execute(string $action): bool
    {
        if (!$this->getCollectionName()) {
            return false;
        }

        $options = [
            'writeConcern' => new WriteConcern($this->getWriteConcern()),
        ];

        if ($result = $this->getBuilder()->$action($this, $options)) {
            $this->syncOriginalDocumentAttributes();
        }

        return $result;
    }
}
