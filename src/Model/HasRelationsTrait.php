<?php
namespace Mongolid\Model;

use Illuminate\Support\Str;
use Mongolid\Model\Relations\EmbedsMany;
use Mongolid\Model\Relations\EmbedsOne;
use Mongolid\Model\Relations\ReferencesMany;
use Mongolid\Model\Relations\ReferencesOne;

/**
 * It is supposed to be used on model classes in general.
 */
trait HasRelationsTrait
{
    /**
     * The loaded relationships for the model.
     *
     * @var array
     */
    private $relations = [];

    /**
     * Get a specified relationship.
     *
     * @return mixed
     */
    public function &getRelation(string $relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
     */
    public function relationLoaded(string $key): bool
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the given relationship on the model.
     *
     * @param mixed $value
     */
    public function setRelation(string $relation, $value): void
    {
        $this->relations[$relation] = $value;
    }

    /**
     * Unset a loaded relationship.
     */
    public function unsetRelation(string $relation): void
    {
        unset($this->relations[$relation]);
    }

    /**
     * Create a ReferencesOne Relation.
     *
     * @param string      $entity    class of the entity or of the schema of the entity
     * @param string|null $field     the field where the $key is stored
     * @param string      $key       the field that the document will be referenced by (usually _id)
     * @param bool        $cacheable retrieves a CacheableCursor instead
     */
    protected function referencesOne(
        string $entity,
        string $field = null,
        string $key = '_id',
        bool $cacheable = true
    ): ReferencesOne {
        $relationName = $this->guessRelationName();
        $field = $field ?: $this->inferFieldForReference($relationName, $key, false);

        return new ReferencesOne($this, $entity, $field, $relationName, $key, $cacheable);
    }

    /**
     * Create a ReferencesMany Relation.
     *
     * @param string      $entity    class of the entity or of the schema of the entity
     * @param string|null $field     the field where the _ids are stored
     * @param bool        $cacheable retrieves a CacheableCursor instead
     */
    protected function referencesMany(
        string $entity,
        string $field = null,
        string $key = '_id',
        bool $cacheable = true
    ): ReferencesMany {
        $relationName = $this->guessRelationName();
        $field = $field ?: $this->inferFieldForReference($relationName, $key, true);

        return new ReferencesMany($this, $entity, $field, $relationName, $key, $cacheable);
    }

    /**
     * Create a EmbedsOne Relation.
     *
     * @param string|null $entity class of the entity or of the schema of the entity
     * @param string      $field  field where the embedded document is stored
     */
    protected function embedsOne(string $entity, string $field = null): EmbedsOne
    {
        $relationName = $this->guessRelationName();
        $field = $field ?: $this->inferFieldForEmbed($relationName);

        return new EmbedsOne($this, $entity, $field, $relationName);
    }

    /**
     * Create a EmbedsMany Relation.
     *
     * @param string|null $entity class of the entity or of the schema of the entity
     * @param string      $field  field where the embedded documents are stored
     */
    protected function embedsMany(string $entity, string $field = null): EmbedsMany
    {
        $relationName = $this->guessRelationName();
        $field = $field ?: $this->inferFieldForEmbed($relationName);

        return new EmbedsMany($this, $entity, $field, $relationName);
    }

    /**
     * Retrieve relation name. For example, if we have a code like this:
     *
     * ```
     * class User extends AbstractActiveRecord
     * {
     *     public function brother()
     *     {
     *         return $this->referencesOne(User::class);
     *     }
     * }
     * ```
     * we will retrieve `brother` as the relation name.
     * This is useful for storing the "Brother Reference"
     * on a field called `brother_id`.
     */
    private function guessRelationName(): string
    {
        [$method, $relationType, $relation] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        // TODO validate that the relation has different name from field?
        return $relation['function'];
    }

    /**
     * Infer field name for reference relations.
     * This is useful for storing the relation on
     * a field based on both the relation name and the
     * referenced key used.
     *
     * @example a `parent` relation on a `code` field
     * would be infered as `parent_code`.
     * @example a `addresses` relation on `_id` field
     * would be infered as `addresses_ids`.
     */
    private function inferFieldForReference(string $relationName, string $key, bool $plural): string
    {
        $relationName = Str::snake($relationName);
        $key = $plural ? Str::plural($key) : $key;

        return $relationName.'_'.ltrim($key, '_');
    }

    /**
     * Infer field name for embed relations.
     * This is useful for storing the relation on
     * a field based on the relation name.
     *
     * @example a `comments` relation on would be infered as `embedded_comments`.
     * @example a `tag` relation on would be infered as `embedded_tag`.
     */
    private function inferFieldForEmbed(string $relationName): string
    {
        $relationName = Str::snake($relationName);

        return 'embedded_'.$relationName;
    }
}
