<?php
namespace Mongolid;

use MongoDB\Collection;
use MongoDB\Driver\WriteConcern;
use MongoDB\Exception\BadMethodCallException;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Model\AbstractModel;
use Mongolid\Model\Exception\ModelNotFoundException;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Model\HasLegacyAttributesTrait;
use Mongolid\Model\HasLegacyRelationsTrait;
use Mongolid\Model\ModelInterface;
use Mongolid\Query\Builder;
use Mongolid\Query\ModelMapper;

/**
 * This class was created to keep v2 compatibility.
 *
 * @deprecated Should use Model\AbstractModel instead.
 */
class LegacyRecord implements ModelInterface
{
    use HasLegacyAttributesTrait;
    use HasLegacyRelationsTrait;

    /**
     * The $dynamic property tells if the object will accept additional fields
     * that are not specified in the $fillable or $guarded properties.
     * This is useful if you does not have a strict document format or
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
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field field to where the $obj will be embedded
     * @param mixed  $obj   document or model instance
     */
    public function embed(string $field, $obj)
    {
        $this->shouldReturnCursor = false;
        $relation = $this->$field();

        $relation->add($obj);
        $this->shouldReturnCursor = true;
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of the given $obj.
     *
     * @param string $field name of the field where the $obj is embeded
     * @param mixed  $obj   document, model instance or _id
     */
    public function unembed(string $field, $obj)
    {
        $this->shouldReturnCursor = false;
        $relation = $this->$field();

        $relation->remove($obj);
        $this->shouldReturnCursor = true;
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field name of the field where the reference will be stored
     * @param mixed  $obj   document, model instance or _id to be referenced
     */
    public function attach(string $field, $obj)
    {
        $this->shouldReturnCursor = false;
        $relation = $this->$field();

        $relation->attach($obj);
        $this->shouldReturnCursor = true;
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $obj from inside the given $field.
     *
     * @param string $field field where the reference is stored
     * @param mixed  $obj   document, model instance or _id that have been referenced by $field
     */
    public function detach(string $field, $obj)
    {
        $this->shouldReturnCursor = false;
        $relation = $this->$field();

        $relation->detach($obj);
        $this->shouldReturnCursor = true;
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
                static::class,
                $method
            )
        );
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public static function first($query = [], array $projection = [])
    {
        return self::getBuilderInstance()->first(new static(), $query, $projection);
    }

    /**
     * @inheritdoc
     */
    public static function firstOrFail($query = [], array $projection = [], bool $useCache = false)
    {
        return self::getBuilderInstance()->firstOrFail(new static(), $query, $projection, $useCache);
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
    private static function getBuilderInstance(): Builder
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

    public function bsonSerialize()
    {
        return Container::make(ModelMapper::class)
            ->map($this, array_merge($this->fillable, $this->guarded), $this->dynamic, $this->timestamps);
    }

    public function bsonUnserialize(array $data)
    {
        unset($data['__pclass']);
        $this->fill($data, true);

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
