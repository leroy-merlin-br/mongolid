<?php
namespace Mongolid\Cursor;

use Iterator;

/**
 * This class wraps the query execution and the actuall creation of the driver
 * cursor. By doing this we can, call 'sort', 'skip', 'limit' and others after
 * calling 'where'. Because the mongodb library's MongoDB\Cursor is much more
 * limited (in that regard) than the old driver MongoCursor.
 *
 * @package Mongolid
 */
class EmbeddedCursor implements Iterator
{
    /**
     * Entity class that will be returned while iterating
     *
     * @var string
     */
    public $entityClass;

    /**
     * The actual array of embedded documents
     *
     * @var array
     */
    protected $items = [];

    /**
     * Iterator position (to be used with foreach)
     *
     * @var integer
     */
    private $position = 0;

    /**
     * @param string $entityClass Class of the objects that will be retrieved by the cursor.
     * @param array  $items       The items array.
     */
    public function __construct(string $entityClass, array $items)
    {
        $this->items       = $items;
        $this->entityClass = $entityClass;
    }

    /**
     * Limits the number of results returned
     *
     * @param  integer $amount The number of results to return.
     *
     * @return EmbeddedCursor Returns this cursor.
     */
    public function limit(int $amount)
    {
        $this->items = array_slice($this->items, 0, $amount);

        return $this;
    }

    /**
     * Sorts the results by given fields
     *
     * @param  array $fields An array of fields by which to sort. Each element in the array has as key the field name, and as value either 1 for ascending sort, or -1 for descending sort.
     *
     * @return EmbeddedCursor Returns this cursor.
     */
    public function sort(array $fields)
    {
        // @TODO

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
        $this->items = array_slice($this->items, $amount);

        return $this;
    }

    /**
     * Counts the number of results for this cursor
     *
     * @return integer The number of documents returned by this cursor's query.
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Iterator interface rewind (used in foreach)
     *
     * @return void
     */
    public function rewind()
    {
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
        $document = $this->items[$this->position];

        if ($document instanceof $this->entityClass) {
            return $document;
        }

        $model = new $this->entityClass;

        foreach ($document as $key => $value) {
            $model->$key = $value;
        }

        return $model;
    }

    /**
     * Returns the first element of the cursor
     *
     * @return mixed
     */
    public function first()
    {
        $this->rewind();

        return $this->current();
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
    }

    /**
     * Iterator valid method (used in foreach)
     *
     * @return boolean
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }
}
