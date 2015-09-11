<?php
namespace Zizaco\Mongolid;

use Iterator;
use MongoCursor;

class OdmCursor implements Iterator
{
    /**
     * Model class that will be returned when iterate
     *
     * @var string
     */
    protected $model;

    /**
     * The MongoCursor used to interact with db
     *
     * @var MongoCursor
     */
    protected $cursor;

    /**
     * Iterator position (to be used with foreach)
     *
     * @var integer
     */
    private $position = 0;

    /**
     * OdmCursor constructor. The mongo cursor and the
     * model should be provided
     *
     * @param $cursor MongoCursor
     * @param $model  string
     */
    public function __construct($cursor, $model)
    {
        $this->cursor = $cursor;

        $this->model = $model;

        $this->position = 0;
    }

    /**
     * Calls the MongoCursor method if it exists.
     * This makes possible to run methods like limit, skip,
     * orts, and others.
     *
     * @param $name string
     * @param $args array
     *
     * @return $this|mixed
     */
    public function __call($name, $args)
    {
        if (method_exists($this->cursor, $name)) {
            // Calls the method in MongoCursor
            $result = call_user_func_array([$this->cursor, $name], $args);

            // In case of sort, limit and other methods of the cursor
            // that return itself (for chained method calls), should
            // return $this (OdmCursor object) instead of MongoCursor.
            if (is_object($result) && get_class($result) == 'MongoCursor') {
                return $this;
            } else {
                return $result;
            }
        }

        return trigger_error('Method ' . $name . ' does not exist in OdmCursor nor in MongoCursor.');
    }

    /**
     * Returns the MongoCursor object
     *
     * @return MongoCursor
     */
    public function getCursor()
    {
        return $this->cursor;
    }

    /**
     * Iterator interface rewind (used in foreach)
     *
     */
    public function rewind()
    {
        $this->cursor->rewind();
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
        $model = new $this->model();

        $document = $this->cursor->current();

        if ($model->parseDocument($document)) {
            $model = $model->polymorph($model);

            return $model;
        } else {
            return false;
        }
    }

    /**
     * Convert the cursor instance to an array.
     *
     * @param bool $documentsToArray
     * @param bool $limit
     *
     * @return array
     */
    public function toArray($documentsToArray = true, $limit = false)
    {
        $result = [];

        foreach ($this as $document) {
            if ($documentsToArray) {
                $result[] = $document->getAttributes();
            } else {
                $result[] = $document;
            }
        }

        return $result;
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
        $this->cursor->next();
    }

    /**
     * Iterator valid method (used in foreach)
     */
    public function valid()
    {
        return $this->cursor->valid();
    }

    /**
     * Iterator count method
     */
    public function count()
    {
        return $this->cursor->count();
    }

    /**
     * @param $fields
     *
     * @return $this
     */
    public function sort($fields)
    {
        if ($this->count() > 1) {
            $this->cursor->sort($fields);
        }

        return $this;
    }

    /**
     * Retrieve a list of all documents for the given $field, optionally indexed by $key
     *
     * @param string $field
     * @param string $key
     *
     * @return array
     */
    public function lists($field, $key = null)
    {
        $result = [];

        foreach ($this as $document) {
            if ($key) {
                $result[$document->$key] = $document->$field;
            } else {
                $result[] = $document->$field;
            }
        }

        return $result;
    }

    /**
     * Convert the cursor to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        $result = '';

        $this->limit(20);

        foreach ($this as $document) {
            $result .= (string) $document;
        }

        $result = '[' . $result . ']';

        return $result;
    }

    /**
     * Convert the cursor to its JSON representation.
     *
     * @param int $options
     *
     * @return string
     */
    public function toJson($options = 0)
    {
        $result = '';

        foreach ($this as $document) {
            $result .= $document->toJson($options);
        }

        $result = '[' . $result . ']';

        return $result;
    }
}
