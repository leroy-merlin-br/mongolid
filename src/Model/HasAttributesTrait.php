<?php

namespace Mongolid\Model;

use Exception;
use Illuminate\Support\Str;
use Mongolid\Container\Container;
use stdClass;

/**
 * This trait adds attribute getter, setters and also a useful
 * `fill` method that can be used with $fillable and $guarded
 * properties to make sure that only the correct attributes
 * will be set.
 *
 * It is supposed to be used on model classes in general
 *
 * @mixin HasAttributesInterface
 */
trait HasAttributesTrait
{
    /**
     * Once you put at least one string in this array, only
     * the attributes specified here will be changed
     * with the setAttribute method.
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
     * The model's attributes.
     *
     * @var array
     */
    private $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array
     */
    private $originalAttributes = [];

    /**
     * {@inheritdoc}
     */
    public function fill(
        array $input,
        bool $force = false
    ) {
        $object = Container::make(static::class);

        if ($object instanceof PolymorphableModelInterface) {
            $class = $object->polymorph(array_merge($object->getAttributes(), $input));

            if ($class !== get_class($object)) {
                $originalAttributes = $object->getAttributes();
                $object = new $class();

                foreach ($originalAttributes as $key => $value) {
                    $object->setAttribute($key, $value);
                }
            }
        }

        foreach ($input as $key => $value) {
            if ($force
                || ((!$object->fillable || in_array($key, $object->fillable)) && !in_array($key, $object->guarded))) {
                if ($value instanceof stdClass) {
                    $value = json_decode(json_encode($value), true); // cast to array
                }

                $object->setAttribute($key, $value);
            }
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute(string $key): bool
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * {@inheritdoc}
     */
    public function &getAttribute(string $key)
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'get')) {
            $this->mutableCache[$key] = $this->{$this->buildMutatorMethod($key, 'get')}();

            return $this->mutableCache[$key];
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (!method_exists(self::class, $key) && method_exists($this, $key)) {
            return $this->getRelationResults($key);
        }

        $this->attributes[$key] = null;

        return $this->attributes[$key];
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        foreach ($this->attributes as $field => $value) {
            if (null === $value) {
                $this->cleanAttribute($field);
            }
        }

        return $this->attributes ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanAttribute(string $key)
    {
        unset($this->attributes[$key]);

        if ($this->hasFieldRelation($key)) {
            $this->unsetRelation($this->getFieldRelation($key));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute(string $key, $value)
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'set')) {
            $value = $this->{$this->buildMutatorMethod($key, 'set')}($value);
        }

        if (null === $value) {
            $this->cleanAttribute($key);

            return;
        }

        $this->attributes[$key] = $value;

        if ($this->hasFieldRelation($key)) {
            $this->unsetRelation($this->getFieldRelation($key));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function syncOriginalAttributes()
    {
        try {
            $this->originalAttributes = unserialize(serialize($this->getAttributes()));
        } catch (Exception $e) {
            $this->originalAttributes = $this->getAttributes();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalAttributes(): array
    {
        return $this->originalAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Verify if model has a mutator method defined.
     *
     * @param string $key    attribute name
     * @param string $prefix method prefix to be used (get, set)
     */
    protected function hasMutatorMethod(string $key, $prefix): bool
    {
        $method = $this->buildMutatorMethod($key, $prefix);

        return method_exists($this, $method);
    }

    /**
     * Create mutator method pattern.
     *
     * @param string $key    attribute name
     * @param string $prefix method prefix to be used (get, set)
     */
    protected function buildMutatorMethod(string $key, string $prefix): string
    {
        return $prefix.Str::studly($key).'DocumentAttribute';
    }
}
