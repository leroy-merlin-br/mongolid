<?php

namespace Mongolid\Model;

/**
 * This trait adds attribute getter, setters and also a usefull
 * `fill` method that can be used with $fillable and $guarded
 * properties to make sure that only the correct attributes
 * will be set.
 *
 * It is supossed to be used in model classes in general
 *
 * @package  Mongolid
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
     * the attributes especified here will be changed
     * with the setAttributes method.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that aren't mass assignable. The oposite
     * to the fillable array;
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get an attribute from the model.
     *
     * @param  string $key The attribute to be accessed.
     *
     * @return mixed
     */
    public function getAttribute(string $key)
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
     * Set the model attributes using an array
     *
     * @param  array $input The data that will be used to fill the attributes.
     *
     * @return void
     */
    public function fill(array $input)
    {
        foreach ($input as $key => $value) {
            if ((empty($this->fillable) || in_array($key, $this->fillable)) && ! in_array($key, $this->guarded)) {
                $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string $key Name of the attribute to be unset.
     *
     * @return void
     */
    public function cleanAttribute(string $key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * Set a given attribute on the model.
     *
     * @param  string $key   Name of the atribute to be set.
     * @param  mixed  $value Value to be set.
     *
     * @return void
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Returns the model instance as an Array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve attributes on the model.
     *
     * @param  mixed $key Name of the attribute.
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
    /**
     * Dynamically set attributes on the model.
     *
     * @param  mixed $key   Attribute name.
     * @param  mixed $value Value to be set.
     *
     * @return void
     */
    public function __set($key, $value)
    {
        // Set attribute
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute exists on the model.
     *
     * @param  mixed $key Attribute name.
     *
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->attributes[$key]);
    }
    /**
     * Unset an attribute on the model.
     *
     * @param  mixed $key Attribute name.
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key]);
    }
}
