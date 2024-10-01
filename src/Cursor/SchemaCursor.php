<?php

declare(strict_types=1);

namespace Mongolid\Cursor;

use Iterator;
use IteratorIterator;
use MongoDB\Driver\ReadPreference;
use Mongolid\Connection\Connection;
use Mongolid\Container\Container;
use Mongolid\LegacyRecord;
use MongoDB\Collection;
use MongoDB\Driver\Exception\LogicException;
use Mongolid\DataMapper\EntityAssembler;
use Mongolid\Schema\Schema;

/**
 * This class wraps the query execution and the actual creation of the driver cursor.
 * By doing this we can, call 'sort', 'skip', 'limit' and others after calling
 * 'where'. Because the mongodb library's MongoDB\Cursor is much more
 * limited (in that regard) than the old driver MongoCursor.
 */
class SchemaCursor implements CursorInterface
{
    /**
     * The MongoDB cursor used to interact with db.
     */
    protected ?Iterator $cursor = null;

    /**
     * Iterator position (to be used with foreach).
     */
    protected int $position = 0;

    /**
     * Have the responsibility of assembling the data coming from the database into actual entities.
     */
    protected ?EntityAssembler $assembler = null;

    /**
     * @param Schema     $entitySchema schema that describes the entity that will be retrieved from the database
     * @param Collection $collection   the raw collection object that will be used to retrieve the documents
     * @param string     $command      the command that is being called in the $collection
     * @param array      $params       the parameters of the $command
     */
    public function __construct(
        public Schema $entitySchema,
        protected Collection $collection,
        protected string $command,
        protected array $params
    ) {
    }

    /**
     * Limits the number of results returned.
     *
     * @param int $amount the number of results to return
     */
    public function limit(int $amount): static
    {
        $this->params[1]['limit'] = $amount;

        return $this;
    }

    /**
     * Sorts the results by given fields.
     *
     * @param array $fields An array of fields by which to sort.
     *                      Each element in the array has as key the field name,
     *                      and as value either 1 for ascending sort, or -1 for descending sort.
     */
    public function sort(array $fields): static
    {
        $this->params[1]['sort'] = $fields;

        return $this;
    }

    /**
     * Skips a number of results.
     *
     * @param int $amount the number of results to skip
     */
    public function skip(int $amount): static
    {
        $this->params[1]['skip'] = $amount;

        return $this;
    }

    /**
     * Disable idle timeout of 10 minutes from MongoDB cursor.
     * This method should be called before the cursor was started.
     *
     * @param bool $flag toggle timeout on or off
     */
    public function disableTimeout(bool $flag = true): static
    {
        $this->params[1]['noCursorTimeout'] = $flag;

        return $this;
    }

    /**
     * This describes how the Cursor route the future read operations to the members of a replica set.
     *
     * @see http://php.net/manual/pt_BR/class.mongodb-driver-readpreference.php
     *
     * @param int $mode preference mode that the Cursor will use
     *
     * @see ReadPreference::class To get a glance of the constants available
     */
    public function setReadPreference(int $mode): static
    {
        $this->params[1]['readPreference'] = new ReadPreference($mode);

        return $this;
    }

    /**
     * Counts the number of results documents returned by this cursor's query.
     */
    public function count(): int
    {
        return $this->collection->countDocuments(...$this->params);
    }

    public function params(): array
    {
        return $this->params;
    }

    /**
     * Iterator interface rewind (used in foreach).
     */
    public function rewind(): void
    {
        try {
            $this->getCursor()->rewind();
        } catch (LogicException) {
            $this->fresh();
            $this->getCursor()->rewind();
        }

        $this->position = 0;
    }

    /**
     * Iterator interface current. Return a model object
     * with cursor document. (used in foreach).
     */
    public function current(): mixed
    {
        $document = $this->getCursor()->current();

        if ($document instanceof LegacyRecord) {
            $documentToArray = $document->toArray();
            $this->entitySchema = $document->getSchema();
        } else {
            $documentToArray = (array) $document;
        }

        return $this->getAssembler()->assemble(
            $documentToArray,
            $this->entitySchema
        );
    }

    /**
     * Returns the first element of the cursor.
     */
    public function first(): mixed
    {
        $this->rewind();
        $document = $this->getCursor()->current();

        if (!$document) {
            return null;
        }

        return $this->getAssembler()->assemble($document, $this->entitySchema);
    }

    /**
     * Refresh the cursor in order to be able to perform a rewind and iterate
     * through it again. A new request to the database will be made in the next
     * iteration.
     */
    public function fresh(): void
    {
        $this->cursor = null;
    }

    /**
     * Iterator key method (used in foreach).
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Iterator next method (used in foreach).
     */
    public function next(): void
    {
        ++$this->position;
        $this->getCursor()->next();
    }

    /**
     * Iterator valid method (used in foreach).
     */
    public function valid(): bool
    {
        return $this->getCursor()->valid();
    }

    /**
     * Convert the cursor instance to an array of Objects.
     */
    public function all(): array
    {
        foreach ($this as $document) {
            $result[] = $document;
        }

        return $result ?? [];
    }

    /**
     * Convert the cursor instance to a full associative array.
     */
    public function toArray(): array
    {
        foreach ($this->getCursor() as $document) {
            $result[] = (array) $document;
        }

        return $result ?? [];
    }

    /**
     * Actually returns a Traversable object with the DriverCursor within.
     * If it does not exist yet, create it using the $collection, $command and
     * $params given.
     */
    protected function getCursor(): Iterator
    {
        if (!$this->cursor) {
            $driverCursor = $this->collection->{$this->command}(...$this->params);
            $this->cursor = new IteratorIterator($driverCursor);
            $this->cursor->rewind();
        }

        return $this->cursor;
    }

    /**
     * Retrieves an EntityAssembler instance.
     */
    protected function getAssembler(): EntityAssembler
    {
        if (!$this->assembler) {
            $this->assembler = Container::make(EntityAssembler::class);
        }

        return $this->assembler;
    }

    /**
     * Serializes this object storing the collection name instead of the actual
     * MongoDb\Collection (which is unserializable).
     */
    public function __serialize(): array
    {
        $properties = get_object_vars($this);
        $properties['collection'] = $this->collection->getCollectionName();

        return $properties;
    }

    /**
     * Unserializes this object. Re-creating the database connection.
     */
    public function __unserialize(array $attributes): void
    {
        $connection = Container::make(Connection::class);

        $client = $connection->getClient();
        $db = $client->selectDatabase(
            $connection->defaultDatabase,
            ['document' => 'array']
        );
        $collectionObject = $db->selectCollection($attributes['collection']);

        foreach ($attributes as $key => $value) {
            if ('collection' === $key) {
                continue;
            }

            $this->$key = $value;
        }

        $this->collection = $collectionObject;
    }
}
