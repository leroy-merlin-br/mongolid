<?php
namespace Mongolid\Model;

use Illuminate\Support\Str;
use Mongolid\Model\Exceptions\InvalidFieldNameException;
use Mongolid\Model\Exceptions\NotARelationException;
use Mongolid\Model\Relations\EmbedsMany;
use Mongolid\Model\Relations\EmbedsOne;
use Mongolid\Model\Relations\ReferencesMany;
use Mongolid\Model\Relations\ReferencesOne;
use Mongolid\Model\Relations\RelationInterface;

/**
 * It is supposed to be used on model classes in general.
 */
trait HasRelationsTrait
{
    /**
     * Relation cache.
     *
     * @var RelationInterface[]
     */
    private $relations = [];

    /**
     * The bound between relations and fields.
     *
     * @var array
     */
    private $fieldRelations = [];

    /**
     * Get a specified relationship.
     */
    public function &getRelation(string $relation): RelationInterface
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
     */
    public function setRelation(string $relation, RelationInterface $value, string $field): void
    {
        $this->validateField($relation, $field);
        $this->relations[$relation] = $value;
        $this->fieldRelations[$field] = $relation;
    }

    /**
     * Unset a loaded relationship.
     */
    public function unsetRelation(string $relation): void
    {
        unset($this->relations[$relation]);
    }

    public function &getRelationResults(string $relation)
    {
        if (!$this->relationLoaded($relation) && !$this->$relation() instanceof RelationInterface) {
            throw new NotARelationException("Called method \"{$relation}\" is not a Relation!");
        }

        return $this->getRelation($relation)->getResults();
    }

    public function hasFieldRelation(string $field): bool
    {
        return isset($this->fieldRelations[$field]);
    }

    public function getFieldRelation(string $field): string
    {
        return $this->fieldRelations[$field];
    }

    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field field to where the $obj will be embedded
     * @param mixed  $obj   document or model instance
     */
    public function embed(string $field, &$obj)
    {
        $relation = $this->embedsMany(get_class($this), $field);

        $relation->add($obj);

        return $relation->getResults();
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of the given $obj.
     *
     * @param string $field name of the field where the $obj is embeded
     * @param mixed  $obj   document, model instance or _id
     */
    public function unembed(string $field, $obj)
    {
        $relation = $this->embedsMany(get_class($this), $field);

        $relation->remove($obj);

        return $relation->getResults();
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field name of the field where the reference will be stored
     * @param mixed  $obj   document, model instance or _id to be referenced
     */
    public function attach(string $field, $obj)
    {
        $relation = $this->referencesMany(get_class($this), $field);

        $relation->attach($obj);

        return $relation->getResults();
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $obj from inside the given $field.
     *
     * @param string $field field where the reference is stored
     * @param mixed  $obj   document, model instance or _id that have been referenced by $field
     */
    public function detach(string $field, $obj)
    {
        $relation = $this->referencesMany(get_class($this), $field);

        $relation->detach($obj);

        return $relation->getResults();
    }

    /**
     * Create a ReferencesOne Relation.
     *
     * @param string      $modelClass class of the referenced model
     * @param string|null $field      the field where the $key is stored
     * @param string      $key        the field that the document will be referenced by (usually _id)
     */
    protected function referencesOne(string $modelClass, string $field = null, string $key = '_id'): ReferencesOne
    {
        $relationName = $this->guessRelationName();

        if (!$this->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForReference($relationName, $key, false);

            $relation = new ReferencesOne($this, $modelClass, $field, $key);
            $this->setRelation($relationName, $relation, $field);
        }

        return $this->getRelation($relationName);
    }

    /**
     * Create a ReferencesMany Relation.
     *
     * @param string      $modelClass class of the referenced model
     * @param string|null $field      the field where the _ids are stored
     * @param string      $key        the field that the document will be referenced by (usually _id)
     */
    protected function referencesMany(string $modelClass, string $field = null, string $key = '_id'): ReferencesMany
    {
        $relationName = $this->guessRelationName();

        if (!$this->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForReference($relationName, $key, true);

            $relation = new ReferencesMany($this, $modelClass, $field, $key);
            $this->setRelation($relationName, $relation, $field);
        }

        return $this->getRelation($relationName);
    }

    /**
     * Create a EmbedsOne Relation.
     *
     * @param string      $modelClass class of the embedded model
     * @param string|null $field      field where the embedded document is stored
     */
    protected function embedsOne(string $modelClass, string $field = null): EmbedsOne
    {
        $relationName = $this->guessRelationName();

        if (!$this->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForEmbed($relationName);

            $relation = new EmbedsOne($this, $modelClass, $field);
            $this->setRelation($relationName, $relation, $field);
        }

        return $this->getRelation($relationName);
    }

    /**
     * Create a EmbedsMany Relation.
     *
     * @param string      $modelClass class of the embedded model
     * @param string|null $field      field where the embedded documents are stored
     */
    protected function embedsMany(string $modelClass, string $field = null): EmbedsMany
    {
        $relationName = $this->guessRelationName();

        if (!$this->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForEmbed($relationName);

            $relation = new EmbedsMany($this, $modelClass, $field);
            $this->setRelation($relationName, $relation, $field);
        }

        return $this->getRelation($relationName);
    }

    /**
     * Retrieve relation name. For example, if we have a code like this:
     *
     * ```
     * class User extends AbstractModel
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

        return $relation['function'];
    }

    /**
     * Infer field name for reference relations.
     * This is useful for storing the relation on
     * a field based on both the relation name and the
     * referenced key used.
     *
     * @example a `parent` relation on a `code` field
     * would be inferred as `parent_code`.
     * @example a `addresses` relation on `_id` field
     * would be inferred as `addresses_ids`.
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
     * @example a `comments` relation on would be inferred as `embedded_comments`.
     * @example a `tag` relation on would be inferred as `embedded_tag`.
     */
    private function inferFieldForEmbed(string $relationName): string
    {
        $relationName = Str::snake($relationName);

        return 'embedded_'.$relationName;
    }

    /**
     * Ensure that fieldName is not the same as the relationName.
     * Otherwise, we would ran into trouble using magic accessors for relations.
     */
    private function validateField(string $relationName, string $fieldName): void
    {
        if ($relationName === $fieldName) {
            throw new InvalidFieldNameException(
                "The field for relation \"{$relationName}\" cannot have the same name as the relation"
            );
        }
    }
}
