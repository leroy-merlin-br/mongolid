<?php
namespace Mongolid\Model;

use Exception;
use Illuminate\Support\Str;
use Mongolid\Container\Container;
use Mongolid\Model\Casts\CastResolver;
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
     * with the setDocumentAttribute method.
     *
     * @var string[]
     */
    protected array $fillable = [];

    /**
     * The attributes that are not mass assignable. The opposite
     * to the fillable array;.
     *
     * @var string[]
     */
    protected array $guarded = [];

    /**
     * Check if model should mutate attributes checking
     * the existence of a specific method on model
     * class. Default is false.
     */
    protected bool $mutable = false;

    /**
     * Store mutable attribute values to work with `&__get()`.
     *
     * @var string[]
     */
    protected array $mutableCache = [];

    /**
     * The model's attributes.
     *
     * @var array<string,mixed>
     */
    private array $attributes = [];

    /**
     * The model attribute's original state.
     *
     * @var array<string,mixed>
     */
    private array $originalAttributes = [];

    /**
     * Attributes that are cast to another types when fetched from database.
     */
    protected array $casts = [];

    /**
     * {@inheritdoc}
     */
    public static function fill(
        array $input,
        HasAttributesInterface $object = null,
        bool $force = false
    ): HasAttributesInterface {
        if (!$object) {
            $object = Container::make(static::class);
        }

        if ($object instanceof PolymorphableModelInterface) {
            $class = $object->polymorph(array_merge($object->getDocumentAttributes(), $input));

            if ($class !== $object::class) {
                $originalAttributes = $object->getDocumentAttributes();
                $object = new $class();

                foreach ($originalAttributes as $key => $value) {
                    $object->setDocumentAttribute($key, $value);
                }
            }
        }

        foreach ($input as $key => $value) {
            if ($force
                || ((!$object->fillable || in_array($key, $object->fillable)) && !in_array($key, $object->guarded))) {
                if ($value instanceof stdClass) {
                    $value = json_decode(json_encode($value, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR); // cast to array
                }

                $object->setDocumentAttribute($key, $value);
            }
        }

        return $object;
    }

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
    public function &getDocumentAttribute(string $key): mixed
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'get')) {
            $this->mutableCache[$key] = $this->{$this->buildMutatorMethod($key, 'get')}();

            return $this->mutableCache[$key];
        }

        if ($casterName = $this->casts[$key] ?? null) {
            $caster = CastResolver::resolve($casterName);
            $value = $caster->get($this->attributes[$key] ?? null);

            return $value;
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
    public function getDocumentAttributes(): array
    {
        foreach ($this->attributes as $field => $value) {
            if (null === $value) {
                $this->cleanDocumentAttribute($field);
            }
        }

        return $this->attributes ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanDocumentAttribute(string $key): void
    {
        unset($this->attributes[$key]);

        if ($this->hasFieldRelation($key)) {
            $this->unsetRelation($this->getFieldRelation($key));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDocumentAttribute(string $key, mixed $value): void
    {
        if ($this->mutable && $this->hasMutatorMethod($key, 'set')) {
            $value = $this->{$this->buildMutatorMethod($key, 'set')}($value);
        }

        if ($casterName = $this->casts[$key] ?? null) {
            $caster = CastResolver::resolve($casterName);
            $value = $caster->set($value);
        }

        if (null === $value) {
            $this->cleanDocumentAttribute($key);

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
    public function syncOriginalDocumentAttributes(): void
    {
        try {
            $this->originalAttributes = unserialize(serialize($this->getDocumentAttributes()));
        } catch (Exception) {
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
    protected function hasMutatorMethod(string $key, string $prefix): bool
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
