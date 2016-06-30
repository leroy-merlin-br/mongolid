<?php
namespace Mongolid\Cursor;

use Iterator;
use IteratorIterator;
use Traversable;
use MongoDB\Collection;
use MongoDB\Driver\Cursor as DriverCursor;
use Mongolid\Container\Ioc;
use Mongolid\DataMapper\EntityAssembler;
use Mongolid\Schema;

/**
 * This class wraps the query execution and the actual creation of the driver cursor.
 * By doing this we can, call 'sort', 'skip', 'limit' and others after calling
 * 'where'. Because the mongodb library's MongoDB\Cursor is much more
 * limited (in that regard) than the old driver MongoCursor.
 *
 * @package Mongolid
 */
class Cursor implements Iterator
{
    /**
     * Schema that describes the entity that will be retrieved when iterating through the cursor.
     *
     * @var string
     */
    public $entitySchema;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * The command that is being called in the $collection.
     *
     * @var string
     */
    protected $command;

    /**
     * The parameters of the $command.
     *
     * @var array
     */
    protected $params;

    /**
     * The MongoDB cursor used to interact with db
     *
     * @var DriverCursor
     */
    protected $cursor = null;

    /**
     * Iterator position (to be used with foreach)
     *
     * @var integer
     */
    protected $position = 0;

    /**
     * Have the responsibility of assembling the data coming from the database into actual entities.
     *
     * @var EntityAssembler
     */
    protected $assembler;

    /**
     * @param Schema     $entitySchema Schema that describes the entity that will be retrieved from the database.
     * @param Collection $collection   The raw collection object that will be used to retrieve the documents.
     * @param string     $command      The command that is being called in the $collection.
     * @param array      $params       The parameters of the $command.
     */
    public function __construct(
        Schema $entitySchema,
        Collection $collection,
        string $command,
        array $params
    ) {
        $this->cursor       = null;
        $this->entitySchema = $entitySchema;
        $this->collection   = $collection;
        $this->command      = $command;
        $this->params       = $params;
    }

    /**
     * Limits the number of results returned
     *
     * @param  integer $amount The number of results to return.
     *
     * @return Cursor Returns this cursor.
     */
    public function limit(int $amount)
    {
        $this->params[1]['limit'] = $amount;

        return $this;
    }

    /**
     * Sorts the results by given fields
     *
     * @param  array $fields An array of fields by which to sort.
     *                       Each element in the array has as key the field name,
     *                       and as value either 1 for ascending sort, or -1 for descending sort.
     *
     * @return Cursor Returns this cursor.
     */
    public function sort(array $fields)
    {
        $this->params[1]['sort'] = $fields;

        return $this;
    }

    /**
     * Skips a number of results
     *
     * @param  integer $amount The number of results to skip.
     *
     * @return Cursor Returns this cursor.
     */
    public function skip(int $amount)
    {
        $this->params[1]['skip'] = $amount;

        return $this;
    }

    /**
     * Counts the number of results for this cursor
     *
     * @return integer The number of documents returned by this cursor's query.
     */
    public function count(): int
    {
        return $this->collection->count(...$this->params);
    }

    /**
     * Iterator interface rewind (used in foreach)
     *
     * @return void
     */
    public function rewind()
    {
        $this->getCursor()->rewind();
        $this->position = 0;
    }
    /**
     * Iterator interface current. Return a model object
     * with cursor document. (used in foreach)
     *
     * @return mixed
     */
    public function current()
    {
        $document = $this->getCursor()->current();

        return $this->getAssembler()->assemble($document, $this->entitySchema);
    }

    /**
     * Returns the first element of the cursor
     *
     * @return mixed
     */
    public function first()
    {
        $this->rewind();
        $document = $this->getCursor()->current();

        if (! $document) {
            return null;
        }

        return $this->getAssembler()->assemble($document, $this->entitySchema);
    }
    /**
     * Iterator key method (used in foreach)
     *
     * @return integer
     */
    public function key()
    {
        return $this->position;
    }
    /**
     * Iterator next method (used in foreach)
     *
     * @return void
     */
    public function next()
    {
        ++$this->position;
        $this->getCursor()->next();
    }
    /**
     * Iterator valid method (used in foreach)
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return $this->getCursor()->valid();
    }

    /**
     * Convert the cursor instance to an array of Objects.
     *
     * @return array
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
     *
     * @return array
     */
    public function toArray(): array
    {
        foreach ($this->getCursor() as $document) {
            $result[] = $document;
        }

        return $result ?? [];
    }

    /**
     * Actually returns a Traversable object with the DriverCursor within.
     * If it does not exists yet, create it using the $collection, $command and
     * $params given
     *
     * @return Traversable
     */
    protected function getCursor(): Traversable
    {
        if (! $this->cursor) {
            $driverCursor = $this->collection->{$this->command}(...$this->params);
            $this->cursor = new IteratorIterator($driverCursor);
            $this->cursor->rewind();
        }

        return $this->cursor;
    }

    /**
     * Retrieves an EntityAssembler instance
     *
     * @return EntityAssembler
     */
    protected function getAssembler()
    {
        if (! $this->assembler) {
            $this->assembler = Ioc::make(EntityAssembler::class);
        }

        return $this->assembler;
    }
}
