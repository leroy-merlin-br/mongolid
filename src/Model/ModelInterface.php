<?php
namespace Mongolid\Model;

use MongoDB\BSON\Persistable;
use MongoDB\Collection;
use Mongolid\Cursor\CursorInterface;

/**
 * @property array                     $fillable
 * @property array                     $guarded
 * @property bool                      $dynamic
 * @property bool                      $timestamps
 * @property \MongoDB\BSON\ObjectId    $_id
 * @property \MongoDB\BSON\UTCDateTime $created_at
 * @property \MongoDB\BSON\UTCDateTime $updated_at
 */
interface ModelInterface extends HasAttributesInterface, Persistable
{
    /**
     * Gets a cursor of this kind of entities that matches the query from the
     * database.
     *
     * @param array $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     * @param bool  $useCache   retrieves a CacheableCursor instead
     */
    public static function where(array $query = [], array $projection = [], bool $useCache = false): CursorInterface;

    /**
     * Gets a cursor of this kind of entities from the database.
     */
    public static function all(): CursorInterface;

    /**
     * Gets the first model of this kind that matches the query.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     *
     * @return ModelInterface|null
     */
    public static function first($query = [], array $projection = [], bool $useCache = false);

    /**
     * Gets the first model of this kind that matches the query. If no
     * document was found, throws ModelNotFoundException.
     *
     * @param mixed $query      mongoDB selection criteria
     * @param array $projection fields to project in Mongo query
     *
     * @throws \Mongolid\Model\Exception\ModelNotFoundException If no document was found
     *
     * @return ModelInterface|null
     */
    public static function firstOrFail($query = [], array $projection = [], bool $useCache = false);

    /**
     * Gets the first model of this kind that matches the query. If no
     * document was found, a new model will be returned with the
     * _if field filled.
     *
     * @param mixed $id document id
     *
     * @return ModelInterface|null
     */
    public static function firstOrNew($id);

    /**
     * Retrieve MongoDB's collection name.
     *
     * @throws \Mongolid\Model\Exception\NoCollectionNameException
     */
    public function getCollectionName(): string;

    /**
     * Retrieve MongoDB's collection.
     *
     * @throws \Mongolid\Model\Exception\NoCollectionNameException
     */
    public function getCollection(): Collection;

    /**
     * Getter for $writeConcern attribute.
     */
    public function getWriteConcern(): int;

    /**
     * Setter for $writeConcern attribute.
     *
     * @param int $writeConcern level of write concern for the transaction
     */
    public function setWriteConcern(int $writeConcern): void;

    /**
     * Saves this object into database.
     */
    public function save(): bool;

    /**
     * Insert this object into database.
     */
    public function insert(): bool;

    /**
     * Updates this object in database.
     */
    public function update(): bool;

    /**
     * Deletes this object in database.
     */
    public function delete(): bool;
}
