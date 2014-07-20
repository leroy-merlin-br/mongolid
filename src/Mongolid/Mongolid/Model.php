<?php namespace Mongolid\Mongolid;

use Exception;
use Mongolid\Mongolid\Container\Ioc;
use MongoDate;

class Model
{
    /**
     * MongoClient instance with connection.
     * @var MongoClient
     */
    public static $connection;

    /**
     * Collection's name for this model.
     * @var string
     */
    protected $collection = null;

    /**
     * Write concern to be used when saving model.
     * -1 = Errors Ignored
     * 0 = Unacknowledged
     * 1 = Acknowledged
     * See: http://docs.mongodb.org/manual/core/write-concern/
     *
     * @var integer
     */
    public $writeConcern = 1;

    /**
     * Timestamp is active.
     * @var string
     */
    protected $timestamps = true;

    /**
     * Database for this model.
     * @var string
     */
    protected $database = null;

    /**
     * Model's attributes.
     * @var array
     */
    protected $attributes = array();

    /**
     * All model's attributes at your original state.
     * @var array
     */
    protected $original = array();

    /**
     * Default events.
     * @var array
     */
    protected $events = array(
        true => [
            'saving' => 'updating',
            'saved'  => 'updated',
        ],
        false => [
            'saving' => 'creating',
            'saved'  => 'created',
        ]
    );

    /**
     * Get for all attributes on the model.
     * @return array
     */
    public function attributes()
    {
        return $this->attributes;
    }

    /**
     * Setter for a collection's name
     * @param string $collection
     */
    public function setCollectionName($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Setter for a DB's name
     * @param string $collection
     */
    public function setDatabaseName($db)
    {
        $this->database = $db;
    }

    /**
     * Returns the Mongo collection object
     *
     * @return MongoDB collection
     */
    public function rawCollection()
    {
        return $this->collection();
    }

    /**
     * Persist this model to the DB.
     * @return boolean
     */
    public function save()
    {
        // If model has no collection. For example: Embe
        if (! $this->isPersistable()) {
            return null;
        }

        // Dispatch a event when saving a resource.
        if (! $this->fireBeforeEventsTo('saving')) {
            return false;
        }

        // Prepare attributes before to be saved.
        $attributes = $this->prepareAttributes();

        // Prepare options like writeConcern etc...
        $options = $this->prepareOptions();

        // Save this model with MongoClient
        $result = $this->collection()->save($attributes, $options);

        // Verify is the save() was ok.
        if (isset($result['ok']) && $result['ok']) {

            // adding _id
            if (isset($result['_id']) && $result['_id']) {
                $this->_id = $result['_id'];
            }

            // Firing event.
            if (! $this->fireAfterEventsTo('saved', false)) {
                return false;
            }

            // Parsing document to original attribute.
            $this->parseDocument($this->attributes);
        } else {
            return false;
        }

        return true;
    }

    /**
     * Verify if this model is already persisted at DB.
     * @return boolean
     */
    protected function alreadyPersisted()
    {
        return ! is_null($this->_id);
    }

    /**
     * Validates if collection is presence.
     * @return boolean
     */
    protected function isPersistable()
    {
        return is_string($this->collection);
    }

    /**
     * Resolve what's is the method should be called at fireModelEvent()
     * @param  string $method
     * @return boolean
     */
    protected function fireBeforeEventsTo($method, $halt = true)
    {
        $status = $this->fireModelEvent($method, $halt);

        return $status;
    }

    /**
     * Resolve what's is the method should be called at fireModelEvent()
     * @param  string $method
     * @return boolean
     */
    protected function fireAfterEventsTo($method, $halt = true)
    {
        $status = false;

        if (isset($this->events[$this->alreadyPersisted()][$method])) {
            $method = $this->events[$this->alreadyPersisted()][$method];
            $status = $this->fireModelEvent($method, $halt);
        }

        return $status;
    }

     /**
     * This method will can be overwritten in order to fire events to the
     * application. This gives an opportunities to implement the observer design
     * pattern.
     *
     * @param  string $event
     * @param  bool   $halt
     * @return mixed
     */
    protected function fireModelEvent($event, $halt = true)
    {
        return true;
    }

    /**
     * Parses a BSON document array into model attributes.
     * Returns true on success.
     *
     * @param array $document
     * @return bool
     */
    protected function parseDocument($document)
    {
        if (! is_array($document)) {
            return false;
        }

        try {
            // For each attribute, feed the model object
            foreach ($document as $field => $value) {
                $this->setAttribute($field, $value);
            }

            // Define this attributes as the original
            $this->original = $this->attributes;
            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    protected function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Prepare options to method save(), update(), insert(), delete() at DB like
     * writeConcern.
     * @return array
     */
    protected function prepareOptions()
    {
        $options = [];

        $options['w'] = $this->writeConcern;

        return $options;
    }

     /**
     * Determine if an attribute exists on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Convert the model to its string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function __set($key, $value)
    {
        // Set attribute
        $this->setAttribute($key, $value);
    }

    /**
     * Returns a new instance of the current model.
     *
     * @return  mixed An instance of the current model.
     */
    public static function newInstance()
    {
        return new static;
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $inAttributes = array_key_exists($key, $this->attributes);

        if ($inAttributes) {
            return $this->attributes[$key];
        } elseif ($key == 'attributes') {
            return $this->attributes;
        } else {
            return null;
        }
    }

    /**
     * Returns the model instance as JSON.
     *
     * @param  int  $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->attributes, $options);
    }

    /**
     * Returns the Mongo collection object
     *
     * @return MongoDB collection
     */
    protected function collection()
    {
        return $this->db()->{$this->collection};
    }

    /**
     * Returns the database object (the connection).
     *
     * @return Mongolid\Mongolid\Connection\Connection
     */
    protected function db()
    {
        if (! static::$connection) {
            $connector = Ioc::make('Mongolid\Mongolid\Connection\Connection');
            static::$connection = $connector->createConnection();
        }
        return static::$connection->{$this->database};
    }

    /**
     * Prepare attributes to be used in MongoDb.
     * especially the _id.
     *
     * @param array $attr
     * @return array
     */
    protected function prepareAttributes()
    {
        $attributes = $this->attributes;

        // Translate the primary key field into _id
        if (isset($attributes['_id'])) {
            if ($this->isMongoId($attributes['_id'])) {
                $attributes['_id'] = new \MongoId($attributes['_id']);
            } elseif (is_numeric($attributes['_id'])) {
                $attributes['_id'] = (int)$attributes['_id'];
            } else {
                $attributes['_id'] = $attributes['_id'];
            }
        }

        $attributes = $this->prepareTimestamps($attributes);

        return $attributes;
    }

    /**
     * This method set at attributes created_at and updated_at fields.
     *
     * @return void
     */
    protected function prepareTimestamps($attr)
    {
        if ($this->timestamps) {
            if (! array_key_exists('created_at', $attr)) {
                $attr['created_at'] = new MongoDate;
            }
            $attr['updated_at'] = new MongoDate;
        }

        return $attr;
    }

    /**
     * Checks if a string is a MongoID
     *
     * @param string $string String to be checked.
     * @return boolean
     */
    protected function isMongoId($string)
    {
        // If its a 24 digits hexadecimal, then it's a MongoId
        return (is_string($string) && strlen($string) == 24 && ctype_xdigit($string));
    }
}
