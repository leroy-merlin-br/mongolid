<?php
namespace Mongolid\Model;

use Mongolid\Model\Relations\EmbedsMany;
use Mongolid\Model\Relations\EmbedsOne;
use Mongolid\Model\Relations\ReferencesMany;
use Mongolid\Model\Relations\ReferencesOne;

/**
 * It is supposed to be used in model classes in general.
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
     * Returns the referenced document as object.
     *
     * @param string $entity    class of the entity or of the schema of the entity
     * @param string $field     the field where the _id is stored
     * @param bool   $cacheable retrieves a CacheableCursor instead
     */
    protected function referencesOne(string $entity, string $field, bool $cacheable = true): ReferencesOne
    {
        return new ReferencesOne($this, $entity, $field, $cacheable);
    }

    /**
     * Returns the cursor for the referenced document objects.
     *
     * @param string $entity    class of the entity or of the schema of the entity
     * @param string $field     the field where the _ids are stored
     * @param bool   $cacheable retrieves a CacheableCursor instead
     */
    protected function referencesMany(string $entity, string $field, bool $cacheable = true): ReferencesMany
    {
        return new ReferencesMany($this, $entity, $field, $cacheable);
    }

    /**
     * Return first embedded document as object.
     *
     * @param string $entity class of the entity or of the schema of the entity
     * @param string $field  field where the embedded document is stored
     */
    protected function embedsOne(string $entity, string $field): EmbedsOne
    {
        return new EmbedsOne($this, $entity, $field);
    }

    /**
     * Return embedded documents cursor.
     *
     * @param string $entity class of the entity or of the schema of the entity
     * @param string $field  field where the embedded documents are stored
     */
    protected function embedsMany(string $entity, string $field): EmbedsMany
    {
        return new EmbedsMany($this, $entity, $field);
    }

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
     * @param  mixed $value
     */
    public function setRelation(string $relation, $value)
    {
        $this->relations[$relation] = $value;
    }

    /**
     * Unset a loaded relationship.
     *
     * @param  string $relation
     */
    public function unsetRelation($relation)
    {
        unset($this->relations[$relation]);
    }

    public function getRelations(): array
    {
        return $this->relations;
    }
}
