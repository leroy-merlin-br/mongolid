<?php
namespace Mongolid\Model;

use Exception;
use Illuminate\Support\Str;
use Mongolid\Model\Relations\NotARelationException;
use Mongolid\Model\Relations\RelationInterface;

/**
 * This trait adds attribute getter, setters and also a useful
 * `fill` method that can be used with $fillable and $guarded
 * properties to make sure that only the correct attributes
 * will be set.
 *
 * It is supposed to be used in model classes in general
 */
trait HasAttributesTrait
{
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
     * Once you put at least one string in this array, only
     * the attributes specified here will be changed
     * with the setDocumentAttributes method.
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
     * {@inheritdoc}
     */
    public function hasDocumentAttribute(string $key): bool
    {
        return !is_null($this->getDocumentAttribute($key));
    }

    /**
     * {@inheritdoc}
     */
    public function &getDocumentAttribute(string $key)
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'get')) {
            $this->mutableCache[$key] = $this->{$this->buildMutatorMethod($key, 'get')}();

            return $this->mutableCache[$key];
        }

        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (!method_exists(self::class, $key) && method_exists($this, $key)) {
             return $this->getRelationValue($key);
        }

        $this->attributes[$key] = null;

        return $this->attributes[$key];
    }

    private function &getRelationValue(string $method)
    {
        if (!$this->relationLoaded($method)) {
            $relation = $this->$method();

            if (!$relation instanceof RelationInterface) {
                throw new NotARelationException("Called method \"{$method}\" is not a Relation!");
            }

            $this->setRelation($method, $relation->getResults());
        }

        return $this->getRelation($method);
    }

    /**
     * {@inheritdoc}
     */
    public function getDocumentAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function fill(array $input, bool $force = false)
    {
        foreach ($input as $key => $value) {
            if ($force
                || ((!$this->fillable || in_array($key, $this->fillable)) && !in_array($key, $this->guarded))) {
                $this->setDocumentAttribute($key, $value);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanDocumentAttribute(string $key)
    {
        unset($this->attributes[$key]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDocumentAttribute(string $key, $value)
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'set')) {
            $value = $this->{$this->buildMutatorMethod($key, 'set')}($value);
        }

        $this->attributes[$key] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function syncOriginalDocumentAttributes()
    {
        try {
            $this->originalAttributes = unserialize(serialize($this->getDocumentAttributes()));
        } catch (Exception $e) {
            $this->originalAttributes = $this->getDocumentAttributes();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOriginalDocumentAttributes(): array
    {
        return $this->originalAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(): array
    {
        return $this->getDocumentAttributes();
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
