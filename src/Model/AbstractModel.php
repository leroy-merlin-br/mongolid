<?php
namespace Mongolid\Model;

use Illuminate\Contracts\Container\BindingResolutionException;
use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Query\Builder;
use Mongolid\Query\ModelMapper;

/**
 * The Mongolid\Model\Model base class will ensure to enable your model to
 * have methods to interact with the database. It means that 'save', 'insert',
 * 'update', 'where', 'first' and 'all' are available within every instance.
 */
abstract class AbstractModel implements ModelInterface
{
    use HasAttributesTrait;
    use HasRelationsTrait;

    /**
     * This attribute is used to eager load models for
     * referenced ids. You can eager load any children
     * models using this parameter. Every time this
     * model is queried, it will load its referenced
     * models together.
     *
     * @var array
     */
    public $with = [];

    /**
     * The $dynamic property tells if the object will accept additional fields
     * that are not specified in the $fillable or $guarded properties.
     * This is useful if you do not have a strict document format or
     * if you want to take full advantage of the "schemaless" nature of MongoDB.
     *
     * @var bool
     */
    protected $dynamic = true;

    /**
     * Whether the model should manage the `created_at` and `updated_at`
     * timestamps automatically.
     *
     * @var bool
     */
    protected $timestamps = true;

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
     * Gets a cursor of this kind of entities that matches the query from the
     * database.
     *
     * @param array $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves a CacheableCursor instead
     */
    public static function where(array $query = [], array $projection = [], bool $useCache = false): CursorInterface
    {
        return self::getBuilderInstance()->where(new static(), $query, $projection, $useCache);
    }

    /**
     * Gets a cursor of this kind of entities from the database.
     */
    public static function all(): CursorInterface
    {
        return self::getBuilderInstance()->all(new static());
    }

    /**
     * Gets the first model of this kind that matches the query.
     *
     * @param mixed   $query      mongoDB selection criteria
     * @param array   $projection fields to project in Mongo query
     * @param boolean $useCache   retrieves the first through a CacheableCursor
     *
     * @return AbstractModel|null
     */
    public static function first($query = [], array $projection = [], bool $useCache = false)
    {
        return self::getBuilderInstance()->first(new static(), $query, $projection, $useCache);
    }

    /**
     * Gets the first model of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed   $query      mongoDB selection criteria
     * @param array   $projection fields to project in Mongo quer
     * @param boolean $useCache   retrieves the first through a CacheableCursor
     *
     * @throws ModelNotFoundException If no document was found
     *
     * @return AbstractModel|null
     */
    public static function firstOrFail($query = [], array $projection = [], bool $useCache = false)
    {
        return self::getBuilderInstance()->firstOrFail(new static(), $query, $projection);
    }

    /**
     * Gets the first model of this kind that matches the query. If no
     * document was found, a new model will be returned with the
     * _if field filled.
     *
     * @param mixed $id document id
     *
     * @return AbstractModel|null
     */
    public static function firstOrNew($id)
    {
        if (!$model = self::first($id)) {
            $model = new static();
            $model->_id = $id;
        }

        return $model;
    }

    /**
     * Returns a valid instance from Ioc.
     */
    protected static function getBuilderInstance(): Builder
    {
        $instance = new static();

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
        if ($this->isSoftDeleteEnabled ?? false) {
            return $this->executeSoftDelete();
        }

        return $this->execute('delete');
    }

    /**
     * Query model on database to retrieve an updated version of its attributes.
     * @return self
     */
    public function fresh(): self
    {
        return $this->first($this->_id);
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
     * {@inheritdoc}
     */
    public function getCollectionName(): string
    {
        if (!$this->collection) {
            throw new NoCollectionNameException();
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function getCollection(): Collection
    {
        $connection = Container::make(Connection::class);

        $database = $connection->defaultDatabase;
        $collectionName = $this->getCollectionName();

        return $connection->getClient()->$database->$collectionName;
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
     * @param int $writeConcern level of write concern for the transaction
     */
    public function setWriteConcern(int $writeConcern): void
    {
        $this->writeConcern = $writeConcern;
    }

    /**
     * @throws BindingResolutionException
     */
    public function bsonSerialize(): object|array
    {
        return Container::make(ModelMapper::class)
            ->map($this, array_merge($this->fillable, $this->guarded), $this->dynamic, $this->timestamps);
    }

    public function bsonUnserialize(array $data): void
    {
        unset($data['__pclass']);
        static::fill($data, $this, true);

        $this->syncOriginalDocumentAttributes();
    }

    /**
     * Performs the given action into database.
     *
     * @param string $action Builder function to execute
     */
    protected function execute(string $action): bool
    {
        $options = [
            'writeConcern' => new WriteConcern($this->getWriteConcern()),
        ];

        if ($result = $this->getBuilder()->$action($this, $options)) {
            $this->syncOriginalDocumentAttributes();
        }

        return $result;
    }

    /**
     * Returns a Builder configured with the collection described
     * in this model.
     */
    private function getBuilder(): Builder
    {
        return Container::make(Builder::class);
    }
}
