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
trait HasLegacyRelationsTrait
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
        return $this->getRelationService()->referencesOne($this, $modelClass, $field, $key);
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
        return $this->getRelationService()->referencesMany($this, $modelClass, $field, $key);
    }

    /**
     * Create a EmbedsOne Relation.
     *
     * @param string      $modelClass class of the embedded model
     * @param string|null $field      field where the embedded document is stored
     */
    protected function embedsOne(string $modelClass, string $field = null): EmbedsOne
    {
        return $this->getRelationService()->embedsOne($this, $modelClass, $field);
    }

    /**
     * Create a EmbedsMany Relation.
     *
     * @param string      $modelClass class of the embedded model
     * @param string|null $field      field where the embedded documents are stored
     */
    protected function embedsMany(string $modelClass, string $field = null): EmbedsMany
    {
        return $this->getRelationService()->embedsMany($this, $modelClass, $field);
    }

    private function getRelationService(): RelationsService
    {
        return Container::make(RelationsService::class);
    }
}
