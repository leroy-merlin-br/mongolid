<?php

namespace Mongolid;

use Illuminate\Contracts\Container\BindingResolutionException;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use MongoDB\Exception\BadMethodCallException;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorInterface;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Model\HasLegacyAttributesTrait;
use Mongolid\Model\HasLegacyRelationsTrait;
use Mongolid\Model\ModelInterface;
use Mongolid\Query\ModelMapper;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\HasSchemaInterface;
use Mongolid\Schema\Schema;

/**
 * This class was created to keep v2 compatibility.
 *
 * @deprecated Should use Model\AbstractModel instead.
 */
class LegacyRecord implements ModelInterface, HasSchemaInterface
{
    use HasLegacyAttributesTrait;
    use HasLegacyRelationsTrait;

    /**
     * The $dynamic property tells if the object will accept additional fields
     * that are not specified in the $fields property. This is useful if you
     * does not have a strict document format or if you want to take full
     * advantage of the "schemaless" nature of MongoDB.
     *
     */
    public bool $dynamic = true;

    /**
     * This attribute is used to eager load models for
     * referenced ids. You can eager load any children
     * models using this parameter. Every time this
     * model is queried, it will load its referenced
     * models together.
     *
     * @var array<string,object>
     */
    public array $with = [];

    /**
     * Name of the collection where this kind of Entity is going to be saved or
     * retrieved from.
     *
     */
    protected ?string $collection = null;

    /**
     * @see https://docs.mongodb.com/manual/reference/write-concern/
     *
     */
    protected int $writeConcern = 1;

    /**
     * Describes the Schema fields of the model. Optionally you can set it to
     * the name of a Schema class to be used.
     *
     * @see  Mongolid\Schema\Schema::$fields
     *
     * @var string|string[]
     */
    protected string|array $fields = [
        '_id' => 'objectId',
        'created_at' => 'createdAtTimestamp',
        'updated_at' => 'updatedAtTimestamp',
    ];

    /**
     * Whether the model should manage the `created_at` and `updated_at`
     * timestamps automatically.
     *
     */
    protected bool $timestamps = true;

    /**
     * Gets the first entity of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @return LegacyRecord
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
     * document was found, a new entity will be returned with the
     * _if field filled.
     *
     * @param mixed $id document id
     *
     * @return LegacyRecord
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
     * Saves this object into database.
     */
    public function save(): bool
    {
        return $this->execute('save');
    }

    /**
     * Insert this object into database.
     *
     * @return bool Success
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
        if ($this->isSoftDeleteEnabled ?? false) {
            return $this->executeSoftDelete();
        }

        return $this->execute('delete');
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
     */
    public function getCollectionName(): string
    {
        if (!$this->collection) {
            throw new NoCollectionNameException();
        }

        return $this->collection ?: $this->getSchema()->collection;
    }

    /**
     * Getter for $writeConcern variable.
     */
    public function getWriteConcern(): int
    {
        return $this->writeConcern;
    }

    /**
     * Setter for $writeConcern variable.
     *
     * @param int $writeConcern level of write concern to the transation
     */
    public function setWriteConcern(int $writeConcern): void
    {
        $this->writeConcern = $writeConcern;
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
        $schema->entityClass = static::class;
        $schema->fields = $this->fields;
        $schema->dynamic = $this->dynamic;
        $schema->collection = $this->collection;

        return $schema;
    }

    public function getCollection(): Collection
    {
        return $this->getDataMapper()
            ->getCollection();
    }

    /**
     * @throws BindingResolutionException
     */
    public function bsonSerialize(): array
    {
        return Container::make(ModelMapper::class)
            ->map(
                $this,
                array_merge($this->fillable, $this->guarded),
                $this->dynamic,
                $this->timestamps
            );
    }

    public function bsonUnserialize(array $data): void
    {
        $this->fill($data, true);

        $this->syncOriginalDocumentAttributes();
    }

    /**
     * Query model on database to retrieve an updated version of its attributes.
     */
    public function fresh(): self
    {
        return static::first($this->_id);
    }

    /**
     * Gets the first entity of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves the entity through a CacheableCursor
     *
     * @Throws ModelNotFoundException if no document was found
     *
     * @return mixed
     * @Throws NoCollectionNameException
     */
    public static function firstOrFail(
        mixed $query = [],
        array $projection = [],
        bool $useCache = false
    ): mixed {
        return self::getDataMapperInstance()->firstOrFail(
            $query,
            $projection,
            $useCache
        );
    }

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
     * Will check if the current value of $fields property is the name of a
     * Schema class and instantiate it if possible.
     */
    protected function instantiateSchemaInFields(): ?Schema
    {
        if (!is_string($this->fields)) {
            return null;
        }
        if (
            is_subclass_of(
                $instance = Container::make($this->fields),
                Schema::class
            )
        ) {
            return $instance;
        }

        return null;
    }

    /**
     * Performs the given action into database.
     *
     * @param string $action datamapper function to execute
     *
     * @return bool
     */
    protected function execute(string $action)
    {
        if (!$this->getCollectionName()) {
            return false;
        }

        $options = [
            'writeConcern' => new WriteConcern($this->getWriteConcern()),
        ];

        if ($result = $this->getDataMapper()->$action($this, $options)) {
            $this->syncOriginalAttributes();
        }

        return $result;
    }

    /**
     * Returns the valid instance from Ioc.
     *
     * @Throws NoCollectionNameException throws exception when has no collection filled
     *
     * @return mixed
     */
    protected static function getDataMapperInstance(): mixed
    {
        $instance = Container::make(static::class);

        if (!$instance->getCollectionName()) {
            throw new NoCollectionNameException();
        }

        return $instance->getDataMapper();
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param mixed $method     name of the method that is being called
     * @param mixed $parameters parameters of $method
     *
     * @Throws BadMethodCallException in case of invalid methods be called
     *
     * @return mixed
     */
    public function __call(mixed $method, mixed $parameters)
    {
        $value = $parameters[0] ?? null;

        // Alias to attach
        if (str_starts_with($method, 'attachTo')) {
            $field = lcfirst(substr($method, 8));

            return $this->attach($field, $value);
        }

        // Alias to embed
        if (str_starts_with($method, 'embedTo')) {
            $field = lcfirst(substr($method, 7));

            return $this->embed($field, $value);
        }

        throw new BadMethodCallException(
            sprintf(
                'The following method can not be reached or does not exist: %s@%s',
                static::class,
                $method
            )
        );
    }
}
