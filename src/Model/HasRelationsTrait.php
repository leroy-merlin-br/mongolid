<?php
namespace Mongolid\Model;

use Illuminate\Support\Str;
use Mongolid\Container\Container;
use Mongolid\Model\Exception\InvalidFieldNameException;
use Mongolid\Model\Exception\NotARelationException;
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
     * Create a ReferencesOne Relation.
     *
     * @param string      $modelClass class of the referenced model
     * @param string|null $field      the field where the $key is stored
     * @param string      $key        the field that the document will be referenced by (usually _id)
     */
    protected function referencesOne(string $modelClass, string $field = null, string $key = '_id'): ReferencesOne
    {
        $relationName = $this->guessRelationName();

        return $this->getRelationsService()->referencesOne($this, $relationName, $modelClass, $field, $key);
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

        return $this->getRelationsService()->referencesMany($this, $relationName, $modelClass, $field, $key);
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

        return $this->getRelationsService()->embedsOne($this, $relationName, $modelClass, $field);
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

        return $this->getRelationsService()->embedsMany($this, $relationName, $modelClass, $field);
    }

    private function getRelationsService(): RelationsService
    {
        if (!$this->relationsService) {
            $this->relationsService = Container::make(RelationsService::class);
        }

        return $this->relationsService;
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
    public function guessRelationName(): string
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
