<?php
namespace Mongolid\Model;

use Illuminate\Support\Str;
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
    public function referencesOne(ModelInterface $model, string $relationName, string $modelClass, string $field = null, string $key = '_id'): ReferencesOne
    {
        if (!$model->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForReference($relationName, $key, false);

            $relation = new ReferencesOne($model, $modelClass, $field, $key);
            $model->setRelation($relationName, $relation, $field);
        }

        return $model->getRelation($relationName);
    }

    /**
     * Create a ReferencesMany Relation.
     *
     * @param string      $modelClass class of the referenced model
     * @param string|null $field      the field where the _ids are stored
     * @param string      $key        the field that the document will be referenced by (usually _id)
     */
    public function referencesMany(ModelInterface $model, string $relationName, string $modelClass, string $field = null, string $key = '_id'): ReferencesMany
    {
        if (!$model->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForReference($relationName, $key, true);

            $relation = new ReferencesMany($model, $modelClass, $field, $key);
            $model->setRelation($relationName, $relation, $field);
        }

        return $model->getRelation($relationName);
    }

    /**
     * Create a EmbedsOne Relation.
     *
     * @param string      $modelClass class of the embedded model
     * @param string|null $field      field where the embedded document is stored
     */
    public function embedsOne(ModelInterface $model, string $relationName, string $modelClass, string $field = null): EmbedsOne
    {
        if (!$model->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForEmbed($relationName);

            $relation = new EmbedsOne($model, $modelClass, $field);
            $model->setRelation($relationName, $relation, $field);
        }

        return $model->getRelation($relationName);
    }

    /**
     * Create a EmbedsMany Relation.
     *
     * @param string      $modelClass class of the embedded model
     * @param string|null $field      field where the embedded documents are stored
     */
    public function embedsMany(ModelInterface $model, string $relationName, string $modelClass, string $field = null): EmbedsMany
    {
        if (!$model->relationLoaded($relationName)) {
            $field = $field ?: $this->inferFieldForEmbed($relationName);

            $relation = new EmbedsMany($model, $modelClass, $field);
            $model->setRelation($relationName, $relation, $field);
        }

        return $model->getRelation($relationName);
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
}
