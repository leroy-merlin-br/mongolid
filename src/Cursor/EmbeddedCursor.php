<?php

namespace Mongolid\Cursor;

use Mongolid\ActiveRecord;
use Mongolid\Container\Ioc;
use Mongolid\DataMapper\EntityAssembler;
use Mongolid\Schema\DynamicSchema;
use Mongolid\Schema\Schema;

/**
 * This class wraps the query execution and the actual creation of the driver cursor.
 * By doing this we can use 'sort', 'skip', 'limit' and others after calling 'where'.
 * Because the mongodb library's MongoDB\Cursor is much more
 * limited (in that regard) than the old driver MongoCursor.
 */
class EmbeddedCursor implements CursorInterface
{
    /**
     * Entity class that will be returned while iterating.
     *
     * @var string
     */
    public $entityClass;

    /**
     * The actual array of embedded documents.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Iterator position (to be used with foreach).
     *
     * @var int
     */
    private $position = 0;

    /**
     * @param string $entityClass class of the objects that will be retrieved by the cursor
     * @param array  $items       the items array
     */
    public function __construct(string $entityClass, array $items)
    {
        $this->items = $items;
        $this->entityClass = $entityClass;
    }

    /**
     * Limits the number of results returned.
     *
     * @param int $amount the number of results to return
     *
     * @return EmbeddedCursor returns this cursor
     */
    public function limit(int $amount)
    {
        $this->items = array_slice($this->items, 0, $amount);

        return $this;
    }

    /**
     * Sorts the results by given fields.
     *
     * @param array $fields An array of fields by which to sort.
     *                      Each element in the array has as key the field name,
     *                      and as value either 1 for ascending sort, or -1 for descending sort.
     *
     * @return EmbeddedCursor returns this cursor
     */
    public function sort(array $fields)
    {
        foreach (array_reverse($fields) as $key => $direction) {
            // Uses usort with a function that will access the $key and sort in
            // the $direction. It mimics how the mongodb does sorting internally.
            usort(
                $this->items,
                function ($a, $b) use ($key, $direction) {
                    $a = is_object($a)
                        ? ($a->$key ?? null)
                        : ($a[$key] ?? null);

                    $b = is_object($b)
                        ? ($b->$key ?? null)
                        : ($b[$key] ?? null);

                    return ($a <=> $b) * $direction;
                }
            );
        }

        return $this;
    }

    /**
     * Skips a number of results.
     *
     * @param int $amount the number of results to skip
     *
     * @return EmbeddedCursor returns this cursor
     */
    public function skip(int $amount)
    {
        $this->items = array_slice($this->items, $amount);

        return $this;
    }

    /**
     * Counts the number of results for this cursor.
     *
     * @return int the number of documents returned by this cursor's query
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Iterator interface rewind (used in foreach).
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * Iterator interface current. Return a model object
     * with cursor document. (used in foreach).
     *
     * @return mixed
     */
    public function current()
    {
        if (!$this->valid()) {
            return;
        }

        $document = $this->items[$this->position];

        if ($document instanceof $this->entityClass) {
            return $document;
        }

        $schema = $this->getSchemaForEntity();
        $entityAssembler = Ioc::makeWith(EntityAssembler::class, compact('schema'));

        return $entityAssembler->assemble($document, $schema);
    }

    /**
     * Retrieve a schema based on Entity Class.
     *
     * @return Schema
     */
    protected function getSchemaForEntity(): Schema
    {
        if ($this->entityClass instanceof Schema) {
            return $this->entityClass;
        }

        $model = new $this->entityClass();

        if ($model instanceof ActiveRecord) {
            return $model->getSchema();
        }

        return new DynamicSchema();
    }

    /**
     * Returns the first element of the cursor.
     *
     * @return mixed
     */
    public function first()
    {
        $this->rewind();

        return $this->current();
    }

    /**
     * Iterator key method (used in foreach).
     *
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Iterator next method (used in foreach).
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * Iterator valid method (used in foreach).
     *
     * @return bool
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }

    /**
     * Convert the cursor instance to an array of Items.
     *
     * @return array
     */
    public function all(): array
    {
        foreach ($this as $item) {
            $results[] = $item;
        }

        return $results ?? [];
    }

    /**
     * Return the raw cursor items.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->items;
    }
}
