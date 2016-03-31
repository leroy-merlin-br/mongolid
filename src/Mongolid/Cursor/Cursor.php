<?php
namespace Mongolid\Cursor;

use MongoDB\Driver\Cursor as DriverCursor;
use MongoDB\Collection;
use IteratorIterator;
use Iterator;

class Cursor implements Iterator
{
    /**
     * ENtity class that will be returned while iterating
     *
     * @var string
     */
    public $entityClass;

    /**
     * @var Collection
     */
    protected $collection;

    protected $command;

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

    public function __construct(
        string $entityClass,
        Collection $collection,
        string $command,
        array $params
    ) {
        $this->cursor      = null;
        $this->entityClass = $entityClass;
        $this->collection  = $collection;
        $this->command     = $command;
        $this->params      = $params;
    }

    public function limit($amount)
    {
        $this->params[1]['limit'] = $amount;

        return $this;
    }

    public function sort($rules)
    {
        $this->params[1]['sort'] = $rules;

        return $this;
    }

    public function skip($amount)
    {
        $this->params[1]['skip'] = $amount;

        return $this;
    }

    public function count()
    {
        $this->command = 'count';
        return call_user_func_array([$this->collection, $this->command], $this->params);
    }

    /**
     * Iterator interface rewind (used in foreach)
     *
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
        $model = new $this->entityClass;
        $document = $this->getCursor()->current();

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
        $document = $this->getCursor()->current();

        if (! $document) {
            return null;
        }

        $model = new $this->entityClass;

        foreach ($document as $key => $value) {
            $model->$key = $value;
        }

        return $model;
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
        $this->getCursor()->next();
    }
    /**
     * Iterator valid method (used in foreach)
     */
    public function valid()
    {
        return $this->getCursor()->valid();
    }

    /**
     * Actually returns the IteratorIterator object with the DriverCursor within.
     * If it doesn't exists yet, create it using the $collection, $command and
     * $params given
     *
     * @return IteratorIterator
     */
    protected function getCursor(): IteratorIterator
    {
        if (! $this->cursor) {
            $driverCursor = call_user_func_array([$this->collection, $this->command], $this->params);
            $this->cursor = new IteratorIterator($driverCursor);
            $this->cursor->rewind();
        }

        return $this->cursor;
    }
}
