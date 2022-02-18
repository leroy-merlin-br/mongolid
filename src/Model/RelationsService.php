<?php

namespace Mongolid\Model;

use Mongolid\Model\Relations\EmbedsMany;
use Mongolid\Model\Relations\EmbedsOne;
use Mongolid\Model\Relations\ReferencesMany;
use Mongolid\Model\Relations\ReferencesOne;

class RelationsService
{

    /**
     * Create a ReferencesOne Relation.
     *
     * @param string      $modelClass class of the referenced model
     * @param string|null $field      the field where the $key is stored
     * @param string      $key        the field that the document will be referenced by (usually _id)
     */
    public function referencesOne(string $modelClass, string $field = null, string $key = '_id'): ReferencesOne
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
    public function referencesMany(string $modelClass, string $field = null, string $key = '_id'): ReferencesMany
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
    public function embedsOne(string $modelClass, string $field = null): EmbedsOne
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
    public function embedsMany(string $modelClass, string $field = null): EmbedsMany
    {
        $relationName = $this->guessRelationName();

        if (!$this->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForEmbed($relationName);

            $relation = new EmbedsMany($this, $modelClass, $field);
            $this->setRelation($relationName, $relation, $field);
        }

        return $this->getRelation($relationName);
    }

}
