<?php
namespace Mongolid\Cursor;

use Iterator;

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

    public function __construct(string $entityClass, array $items)
    {
        $this->items       = $items;
        $this->entityClass = $entityClass;
    }

    public function limit($amount)
    {
        $this->items = array_slice($this->items, 0, $amount);

        return $this;
    }

    public function sort($rules)
    {
        // @TODO

        return $this;
    }

    public function skip($amount)
    {
        $this->items = array_slice($this->items, $amount);

        return $this;
    }

    public function count()
    {
        return count($this->items);
    }

    /**
     * Iterator interface rewind (used in foreach)
     *
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
     */
    public function key()
    {
        return $this->position;
    }
    /**
     * Iterator next method (used in foreach)
     */
    public function next()
    {
        ++$this->position;
    }
    /**
     * Iterator valid method (used in foreach)
     */
    public function valid()
    {
        return isset($this->items[$this->position]);
    }
}
