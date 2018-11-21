<?php
namespace Mongolid\Model;

use MongoDB\Driver\WriteConcern;
use Mongolid\Container\Ioc;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Exception\ModelNotFoundException;
use Mongolid\Exception\NoCollectionNameException;
use Mongolid\Schema\AbstractSchema;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\HasSchemaInterface;

/**
 * The Mongolid\Model\ActiveRecord base class will ensure to enable your entity to
 * have methods to interact with the database. It means that 'save', 'insert',
 * 'update', 'where', 'first' and 'all' are available within every instance.
 * The Mongolid\Schema\Schema that describes the entity will be generated on the go
 * based on the $fields.
 */
abstract class AbstractActiveRecord implements HasAttributesInterface, HasSchemaInterface
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
     * @param bool  $useCache   retrieves a CacheableCursor instead
     */
    public static function where(
        array $query = [],
        array $projection = [],
        bool $useCache = false
    ): CursorInterface {
        return self::getDataMapperInstance()->where(
            $query,
            $projection,
            $useCache
        );
    }

    /**
     * Gets a cursor of this kind of entities from the database.
     */
    public static function all(): CursorInterface
    {
        return self::getDataMapperInstance()->all();
    }

    /**
     * Gets the first entity of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @return static|null
     */
    public static function first(
        $query = [],
        array $projection = [],
        bool $useCache = false
    ) {
        return self::getDataMapperInstance()->first(
            $query,
            $projection,
            $useCache
        );
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @throws ModelNotFoundException If no document was found
     *
     * @return static|null
     */
    public static function firstOrFail(
        $query = [],
        array $projection = [],
        bool $useCache = false
    ) {
        return self::getDataMapperInstance()->firstOrFail(
            $query,
            $projection,
            $useCache
        );
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, a new entity will be returned with the
     * _if field filled.
     *
     * @param mixed $id document id
     *
     * @return static|null
     */
    public static function firstOrNew($id)
    {
        if ($entity = self::getDataMapperInstance()->first($id)) {
            return $entity;
        }

        $entity = new static();
        $entity->_id = $id;

        return $entity;
    }

    /**
     * Returns a valid instance from Ioc.
     *
     * @throws NoCollectionNameException Throws exception when has no collection filled
     */
    private static function getDataMapperInstance(): DataMapper
    {
        $instance = new static();

        if (!$instance->getCollectionName()) {
            throw new NoCollectionNameException();
        }

        return $instance->getDataMapper();
    }

    /**
     * Parses an object with SchemaMapper.
     *
     * @param mixed $entity the object to be parsed
     */
    public function parseToDocument($entity): array
    {
        return $this->getDataMapper()->parseToDocument($entity);
    }

    /**
     * Saves this object into database.
     */
    public function save()
    {
        return $this->execute('save');
    }

    /**
     * Insert this object into database.
     */
    public function insert()
    {
        return $this->execute('insert');
    }

    /**
     * Updates this object in database.
     */
    public function update()
    {
        return $this->execute('update');
    }

    /**
     * Deletes this object in database.
     */
    public function delete()
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
    public function __set(string $key, $value)
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
    public function __unset(string $key)
    {
        $this->cleanDocumentAttribute($key);
    }

    /**
     * Returns a DataMapper configured with the Schema and collection described
     * in this entity.
     */
    public function getDataMapper(): DataMapper
    {
        $dataMapper = Ioc::make(DataMapper::class);
        $dataMapper->setSchema($this->getSchema());

        return $dataMapper;
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
    public function setWriteConcern(int $writeConcern)
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
        $schema->entityClass = static::class;
        $schema->fields = $this->fields;
        $schema->dynamic = $this->dynamic;
        $schema->collection = $this->collection;

        return $schema;
    }

    /**
     * Will check if the current value of $fields property is the name of a
     * Schema class and instantiate it if possible.
     *
     * @return AbstractSchema|null
     */
    protected function instantiateSchemaInFields()
    {
        if (is_string($this->fields)) {
            if (is_subclass_of($instance = Ioc::make($this->fields), AbstractSchema::class)) {
                return $instance;
            }
        }
    }

    /**
     * Performs the given action into database.
     *
     * @param string $action DataMapper function to execute
     */
    protected function execute(string $action): bool
    {
        if (!$this->getCollectionName()) {
            return false;
        }

        $options = [
            'writeConcern' => new WriteConcern($this->getWriteConcern()),
        ];

        if ($result = $this->getDataMapper()->$action($this, $options)) {
            $this->syncOriginalDocumentAttributes();
        }

        return $result;
    }
}
