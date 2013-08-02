<?php
namespace Zizaco\Mongolid;

use MongoClient, MongoDate;

class Model
{
    /**
     * The connection name for the model.
     *
     * @var MongoDB
     */
    public static $connection;

    /**
     * The collection associated with the model.
     *
     * @var string
     */
    protected $collection = null;

    /**
     * The database associated with the model.
     *
     * @var string
     */
    protected $database = null;

    /**
     * The Laravel's cache component. Or other cache manager that
     * has a method with the same signature
     *
     * @var CacheComponent
     */
    public static $cacheComponent = null;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = array();

    /**
     * Once you put at least one string in this array, only
     * the attributes especified here will be changed
     * with the setAttributes method.
     *
     * @var array
     */
    public $fillable = array();

    /**
     * The attributes that aren't mass assignable. The oposite
     * to the fillable array;
     *
     * @var array
     */
    public $guarded = array();

    /**
     * Save the model to the database.
     *
     * @return bool
     */
    public function save()
    {
        if (! $this->collection) return false;

        $this->prepareTimestamps();
        $preparedAttr = $this->prepareMongoAttributes( $this->attributes );

        $result = $this->collection()
            ->save( $preparedAttr, array("w" => 1) );

        if(isset($result['ok']) && $result['ok'] ) {
            $this->parseDocument($this->attributes);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete the model from the database
     *
     * @return bool
     */
    public function delete()
    {
        $preparedAttr = $this->prepareMongoAttributes( $this->attributes );

        $result = $this->collection()
            ->remove( $preparedAttr );

        if(isset($result['ok']) && $result['ok'] ) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Find one document by id or by query array
     *
     * @param  mixed  $id
     * @param  array  $fields
     * @return Zizaco\Mongolid\MongoModel
     */
    public static function first($id = array(), $fields = array())
    {
        $instance = static::newInstance();

        if (! $instance->collection) return false;

        // Get query array
        $query = $instance->prepareQuery($id);

        // If fields specified then prepare Mongo's projection
        if(! empty($fields)) $fields = $instance->prepareProjection($fields);

        // Perfodm Mongo's findOne
        $document = $instance->collection()->findOne( $query, $fields );

        // If the response is correctly parsed return it
        if( $instance->parseDocument( $document ) ) {
            $instance = $instance->polymorph( $instance );
            return $instance;
        } else {
            return false;
        }
    }

    /**
     * Find one document by id or by query array. Returns
     * a single model if only one document matched the
     * criteria, or a OdmCursor if more than one.
     *
     * @param  mixed  $id
     * @param  array  $fields
     * @param  boolean  $cachable
     * @return mixed
     */
    public static function find($id = array(), $fields = array(), $cachable = false)
    {
        $result = static::where( $id, $fields );
        if( $result->count() == 1 ) {
            $result->rewind();
            return $result->current();
        } else{
            return $result;
        }
    }

    /**
     * Find documents from the collection within the query
     *
     * @param  array  $query
     * @param  array  $fields
     * @param  boolean  $cachable
     * @return Zizaco\Mongolid\OdmCursor
     */
    public static function where($query = array(), $fields = array(), $cachable = false)
    {
        $instance = static::newInstance();

        if (! $instance->collection)
            return false;

        // Get query array
        $query = $instance->prepareQuery($query);

        // If fields specified then prepare Mongo's projection
        if(! empty($fields))
            $fields = $instance->prepareProjection($fields);

        if($cachable) {
            // Perfodm Mongo's find and returns iterable cursor
            $cursor =  new CachableOdmCursor(
                $query,
                get_class($instance)
            );
        } else {
            // Perfodm Mongo's find and returns iterable cursor
            $cursor =  new OdmCursor(
                $instance->collection()->find( $query, $fields ),
                get_class($instance)
            );
        }

        return $cursor;
    }

    /**
     * Find "all" documents from the collection
     *
     * @param  array  $fields
     * @return Zizaco\Mongolid\OdmCursor
     */
    public static function all( $fields = array() )
    {
        return static::where( array(), $fields );
    }

    /**
     * Parses a BSON document array into model attributes.
     * Returns true on success.
     *
     * @param array $doc
     * @return bool
     */
    public function parseDocument( $doc )
    {
        if(! is_array($doc) )
            return false;

        try{
            // For each attribute, feed the model object
            foreach ($doc as $field => $value) {
                $this->setAttribute($field, $value);
            }

            // Define this attributes as the original
            $this->original = $this->attributes;

            // Returns success
            return true;
        }
        catch( Exception $e )
        {
            // Returns fail;
            return false;
        }
    }

    /**
     * Prepare query array for the given id or for the
     * given array.
     *
     * @param  mixed  $id
     * @return array
     */
    protected function prepareQuery($id)
    {
        if (! is_array($id)) {
            // If not an array, then search by _id
            $id = array( '_id' => $id );
        }

        // Prepare query array with attributes
        $query = $this->prepareMongoAttributes($id);

        return $query;
    }

    /**
     * Prepare attributes to be used in MongoDb.
     * especially the _id.
     *
     * @param array $attr
     * @return array
     */
    private function prepareMongoAttributes($attr)
    {
        // Translate the primary key field into _id
        if( isset($attr['_id']) ) {
            // If its a 24 digits hexadecimal, then it's a MongoId
            if ($this->isMongoId($attr['_id'])) {
                $attr['_id'] = new \MongoId( $attr['_id'] );
            } elseif(is_numeric($attr['_id'])) {
                $attr['_id'] = (int)$attr['_id'];
            } else {
                $attr['_id'] = $attr['_id'];
            }
        }

        return $attr;
    }

    /**
     * Prepare Mongo's projection
     *
     * @param  array  $fields
     * @return array
     */
    protected function prepareProjection($fields)
    {
        // Prepare fields array for mongo query
        $fields = array_flip( $fields );
        foreach ($fields as $field => $value) {
            $fields[$field] = 1;
        }

        return $fields;
    }

    /**
     * Returns the database object (the connection)
     *
     * @return MongoDB
     */
    protected function db()
    {
        if(! static::$connection ) {
            $connector = new MongoDbConnector;
            static::$connection = $connector->getConnection();
        }

        return static::$connection->{$this->database};
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
     * Returns the Mongo collection object
     *
     * @return MongoDB collection
     */
    public function rawCollection()
    {
        return $this->collection();
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
     * Get all attributes from the model.
     *
     * @return mixed
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get the _id.
     *
     * @return mixed
     */
    public function getMongoId()
    {
        return $this->getAttribute('_id');
    }

    /**
     * Get the collection used by the object
     *
     * @return string Collection name
     */
    public function getCollectionName()
    {
        return $this->collection;
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Set the model attributes using an array
     *
     * @param  array   $input
     * @return void
     */
    public function fill( $input )
    {
        foreach ($input as $key => $value) {
            if( (empty($this->fillable) or in_array($key,$this->fillable)) && ! in_array($key,$this->guarded) ) {
                $this->setAttribute( $key, $value );
            }
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string  $key
     * @param  mixed   $value
     * @return void
     */
    public function cleanAttribute($key)
    {
        unset( $this->attributes[$key] );
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
     * Returns the model instance as an Array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Returns the referenced documents as objects
     */
    protected function referencesOne($model, $field, $cachable = true)
    {
        $referenced_id = $this->$field;

        if(is_array($referenced_id) && count($referenced_id) == 1 && isset($referenced_id[0])) $referenced_id = $referenced_id[0];

        if($cachable && static::$cacheComponent) {
            $cache_key = 'reference_cache_'.$model.'_'.$this->$field;

            // For the next 30 seconds (0.5 minutes), the last retrived value (for that Collection and ID)
            // will be returned from cache =)
            return static::$cacheComponent->remember($cache_key, 0.5, function() use ($model, $field, $referenced_id)
            {
                return $model::first(array('_id'=>$referenced_id));
            });
        } else {
            return $model::first(array('_id'=>$referenced_id));
        }
    }

    /**
     * Returns the cursor for the referenced documents as objects
     */
    protected function referencesMany($model, $field, $cachable = true)
    {
        $ref_ids = $this->$field;

        if (! isset($ref_ids[0]) ) return array();

        if ($this->isMongoId($ref_ids[0])) {
            foreach ($ref_ids as $key => $value) {
                $ref_ids[$key] = new \MongoId($value);
            }
        }

        if($cachable && static::$cacheComponent) {
            $cache_key = 'reference_cache_'.$model.'_'.md5(serialize($ref_ids));

            // For the next 6 seconds (0.1 minute), the last retrived value
            // will be returned from cache =)
            return static::$cacheComponent->remember($cache_key, 0.1, function() use ($model, $ref_ids)
            {
                return $model::where(array('_id'=>array('$in'=>$ref_ids)), array(), true);
            });
        } elseif($cachable) {
            return $model::where(array('_id'=>array('$in'=>$ref_ids)), array(), true);
        } else {
            return $model::where(array('_id'=>array('$in'=>$ref_ids)));
        }
    }

    /**
     * Return a embedded documents as object
     */
    protected function embedsOne($model, $field)
    {
        $instance = null;
        $field = $this->getAttribute($field);

        if(is_array($field)) {
            if( isset($field[0]) ) {
                $document = $field[0];
            } else {
                $document = $field;
            }
            $instance = new $model;
            $instance->parseDocument( $document );
            $instance = $this->polymorph( $instance );
        }

        return $instance;
    }

    /**
     * Return array of embedded documents as objects
     *
     * @return array Array with the embedded documents
     */
    protected function embedsMany($model, $field)
    {
        $documents = array();

        if(is_array($this->$field))
        {
            foreach ($this->$field as $document) {
                $instance = new $model;
                $instance->parseDocument( $document );
                $instance = $this->polymorph( $instance );
                $documents[] = $instance;
            }
        }

        return $documents;
    }

    /**
     * Attach a new document or id to an reference array
     *
     * @param string $field
     * @param mixed $obj _id, document or model instance
     * @return void
     */
    public function attach($field, $obj)
    {
        if( is_a($obj,'Zizaco\Mongolid\Model') ) {
            $mongoId = $obj->getMongoId();
        } elseif( is_array($obj) ) {
            if(isset($obj['id'])) {
                $mongoId = $obj['id'];
            } elseif(isset($obj['_id'])) {
                $mongoId = $obj['_id'];
            }
        } else {
            $mongoId = $obj;
        }

        if($mongoId != null) {
            $attr = (array)$this->getAttribute($field);
            $attr[] = $mongoId;
            $this->setAttribute($field, array_unique($attr));
        }
    }

    /**
     * Detach a document or id from an reference array
     *
     * @param string $field
     * @param mixed $obj _id, document or model instance
     * @return void
     */
    public function detach($field, $obj)
    {
        if( is_a($obj,'Zizaco\Mongolid\Model') ) {
            $mongoId = $obj->getMongoId();
        } elseif( is_array($obj) ) {
            if(isset($obj['id'])) {
                $mongoId = $obj['id'];
            } elseif(isset($obj['_id'])) {
                $mongoId = $obj['_id'];
            }
        } else {
            $mongoId = $obj;
        }

        if($mongoId != null) {
            $attr = (array)$this->getAttribute($field);

            foreach ($attr as $key => $value) {
                if((string)$value == (string)$mongoId) {
                    unset($attr[$key]);
                }
            }
            $this->setAttribute($field, array_values($attr));
        }
    }

    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field
     * @param mixed $obj _id, document or model instance
     * @return void
     */
    public function embed($field, &$obj)
    {
        if( is_a($obj,'Zizaco\Mongolid\Model') ) {
            $document = $obj->toArray();
        } else {
            $document = $obj;
        }

        if($document != null) {
            $embedded = (array)$this->getAttribute($field);

            if(isset($document['_id'])) {
                foreach ($embedded as $key => $value) {

                    if(isset($value['_id']) && $value['_id'] == $document['_id']) {
                        unset($embedded[$key]);
                        break;
                    }
                }
            } else {
                $generatedId = new \MongoId;
                $document['_id'] = $generatedId;

                if( is_a($obj,'Zizaco\Mongolid\Model') ) {
                    $obj->_id = $generatedId;
                }
            }

            $embedded[] = $document;

            $this->setAttribute($field, array_values($embedded));
        }
    }

    /**
     * Embed a new document to an attribute
     *
     * @param string $field
     * @param mixed $target, document or part of the document. Ex: ['name'='Something']
     * @return void
     */
    public function unembed($field, $target)
    {
        if( is_a($target,'Zizaco\Mongolid\Model') ) {
            $target = $target->toArray();
        } elseif(! is_array($target) ) {
            $target = array('_id' => $target);
        }

        $documents = $this->getAttribute($field);

        // Foreach embedded document
        foreach ($documents as $oKey => $document) {
            // Remove it unless...
            $remove = true;

            // For each key defined in the target obj
            foreach ($target as $tKey => $tValue) {
                if(isset($document[$tKey])) {
                    // The value is equal for the embedded document
                    if($target[$tKey] != $document[$tKey]) {
                        $remove = false;
                    }
                }
            }

            // If not
            if( $remove ) {
                unset($documents[$oKey]); // Remove it
            }
        }

        // Update attribute
        $this->setAttribute($field, array_values($documents));
    }

    /**
     * The polymorphic method is something that may be overwritten
     * in order to make a model polimorphic. For example: You may have three
     * models with the same collection: Content, ArticleContent and VideoContent.
     * By overwriting the polymorph method is possible to make the Content
     * to become a ArticleContent or a VideoContent object by simply
     * selecting it from the database using first, find, where or all.
     *
     * Example:
     *  public function polymorph( $instance )
     *  {
     *      if ($this->video != null)
     *      {
     *          $obj = new VideoContent;
     *          $obj->parseDocument( $instance->attributes );
     *
     *          return $obj;
     *      }
     *      else
     *      {
     *          return $instance;
     *      }
     *  }
     *
     * In the example above, if you call Content::first() and the content
     * returned have the key video set, then the object returned will be
     * a VideoContent instead of a Content.
     *
     */
    public function polymorph( $instance )
    {
        return $instance;
    }

    /**
     * Returns a new instance of the current model
     *
     * @return  mixed An instance of the current model
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

    public function __call($method, $parameters)
    {
        $value = isset($parameters[0]) ? $parameters[0] : null;

        if ('attachTo' == substr($method,0,8)) {
            // Attach a new document or id to an reference array
            $field = strtolower(substr($method,8,1)).substr($method,9);
            $this->attach($field, $value);
        } elseif ('embedTo' == substr($method,0,7)) {
            // Embed a new document or id to an reference array
            $field = strtolower(substr($method,7,1)).substr($method,8);
            $this->embed($field, $value);
        } else {
            throw new \Exception('Call to undefined method '.$method, 1);
        }
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
     * Checks if a string is a MongoID
     *
     * @param string $string String to be checked.
     * @return boolean
     */
    private function isMongoId($string)
    {
        // If its a 24 digits hexadecimal, then it's a MongoId
        return (is_string($string) && strlen($string) == 24 && ctype_xdigit($string));
    }

    /**
     * This method set at attributes created_at and updated_at fields.
     *
     * @return void
     */
    public function prepareTimestamps()
    {
        if (! array_key_exists('created_at', $this->attributes) ) {
            $this->attributes['created_at'] = new MongoDate;
        }
        $this->attributes['updated_at'] = new MongoDate;
    }
}
