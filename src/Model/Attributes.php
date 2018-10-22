<?php
namespace Mongolid\Model;

use Exception;

/**
 * This trait adds attribute getter, setters and also a useful
 * `fill` method that can be used with $fillable and $guarded
 * properties to make sure that only the correct attributes
 * will be set.
 *
 * It is supposed to be used in model classes in general
 */
trait Attributes
{
    /**
     * The model's attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * Once you put at least one string in this array, only
     * the attributes specified here will be changed
     * with the setAttributes method.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that are not mass assignable. The opposite
     * to the fillable array;.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Check if model should mutate attributes checking
     * the existence of a specific method on model
     * class. Default is false.
     *
     * @var bool
     */
    protected $mutable = false;

    /**
     * Store mutable attribute values to work with `&__get()`.
     *
     * @var array
     */
    protected $mutableCache = [];

    /**
     * Get an attribute from the model.
     *
     * @param string $key the attribute to be accessed
     *
     * @return mixed
     */
    public function getAttribute(string $key)
    {
        return $this->{$key};
    }

    /**
     * Get all attributes from the model.
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set the model attributes using an array.
     *
     * @param array $input the data that will be used to fill the attributes
     * @param bool  $force force fill
     */
    public function fill(array $input, bool $force = false)
    {
        foreach ($input as $key => $value) {
            if ($force
                || ((!$this->fillable || in_array($key, $this->fillable)) && !in_array($key, $this->guarded))) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key name of the attribute to be unset
     */
    public function cleanAttribute(string $key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param string $key   name of the attribute to be set
     * @param mixed  $value value to be set
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Stores original attributes from actual data from attributes
     * to be used in future comparisons about changes.
     * It tries to clone the attributes (using serialize/unserialize)
     * so modifications to objects will be correctly identified
     * as changes.
     *
     * Ideally should be called once right after retrieving data from
     * the database.
     */
    public function syncOriginalAttributes()
    {
        try {
            $this->original = unserialize(serialize($this->attributes));
        } catch (Exception $e) {
            $this->original = $this->attributes;
        }
    }

    /**
     * Retrieve original attributes.
     */
    public function getOriginalAttributes(): array
    {
        return $this->original;
    }

    /**
     * Returns the model instance as an Array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param mixed $key name of the attribute
     *
     * @return mixed
     */
    public function &__get($key)
    {
        if ('attributes' === $key) {
            return $this->attributes;
        }

        if ($this->mutable && $this->hasMutatorMethod($key, 'get')) {
            $this->mutableCache[$key] = $this->{$this->buildMutatorMethod($key, 'get')}();

            return $this->mutableCache[$key];
        }

        if (!array_key_exists($key, $this->attributes)) {
            $this->attributes[$key] = null;
        }

        return $this->attributes[$key];
    }

    /**
     * Dynamically set attributes on the model.
     *
     * @param mixed $key   attribute name
     * @param mixed $value value to be set
     */
    public function __set($key, $value)
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'set')) {
            $value = $this->{$this->buildMutatorMethod($key, 'set')}($value);
        }

        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param mixed $key attribute name
     *
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->{$key});
    }

    /**
     * Unset an attribute on the model.
     *
     * @param mixed $key attribute name
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Verify if model has a mutator method defined.
     *
     * @param mixed $key    attribute name
     * @param mixed $prefix method prefix to be used
     *
     * @return bool
     */
    protected function hasMutatorMethod($key, $prefix)
    {
        $method = $this->buildMutatorMethod($key, $prefix);

        return method_exists($this, $method);
    }

    /**
     * Create mutator method pattern.
     *
     * @param mixed $key    attribute name
     * @param mixed $prefix method prefix to be used
     *
     * @return string
     */
    protected function buildMutatorMethod($key, $prefix)
    {
        return $prefix.ucfirst($key).'Attribute';
    }
}
