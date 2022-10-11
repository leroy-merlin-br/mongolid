<?php
namespace Mongolid\Query\EagerLoader;

use MongoDB\BSON\ObjectId;
use Mongolid\Query\EagerLoader\Exception\EagerLoaderException;
use Mongolid\Query\EagerLoader\Exception\InvalidModelKeyException;

/**
 * Responsible for extract ids from the model based on its
 * relationship. You can use dot notations in order to
 * extract ids from your embedded models.
 */
class Extractor
{
    /**
     * This array will handle all related models extracted
     * from the models passed by.
     *
     * @var array
     */
    private $relatedModels;

    public function __construct(array $relatedModels)
    {
        $this->relatedModels = $relatedModels;
    }

    /**
     * The model received here may be either an array from mongodb
     * or a cached model as a serialized array.
     * That's why we always will force an array to be used here.
     * It will ensure that we are only working with model arrays.
     */
    public function extractFrom(array $model): array
    {
        foreach ($this->relatedModels as $eagerLoadKey => $loadModel) {
            $key = $this->extractKeyFrom($loadModel);

            // If the key extract contains '.' notations means that
            // we want to extract models from embedded relations.
            if ($this->keyHasDots($key)) {
                $this->extractFromEmbeddedModel($eagerLoadKey, $model, $key);

                continue;
            }

            $this->extractFromModel($eagerLoadKey, $model, $key);
        }

        return $this->relatedModels;
    }

    public function getRelatedModels(): array
    {
        return $this->relatedModels;
    }

    private function addIdFor(string $eagerLoadKey, $id): void
    {
        $this->relatedModels[$eagerLoadKey]['ids'][$id] = $id;
    }

    /**
     * The user can use dot notations to get models from an
     * embedded relationship. For now, we are only getting
     * the first children for performance reasons.
     *
     * @example 'skus.shop_id' => I want all shop ids from embedded skus.
     */
    private function keyHasDots($key): bool
    {
        return str_contains($key, '.');
    }

    /**
     * Using dot notations, the user can specify what ids on embedded
     * models he wants to extract. So, our job is to loop on every
     * embedded model to get the id specified on the key.
     */
    private function extractFromEmbeddedModel(string $eagerLoadKey, array $model, string $key): void
    {
        list($method, $attribute) = explode('.', $key);

        // As we are working with models as array, this give us
        // flexibility to use it as array instead of calling methods.
        foreach ($model[$method] ?? [] as $embeddedModel) {
            $this->extractFromModel($eagerLoadKey, $embeddedModel, $attribute);
        }
    }

    private function extractFromModel(string $eagerLoadKey, array $model, string $key): void
    {
        if (!$id = $model[$key] ?? false) {
            // In case the referenced key on parent model was not
            // found on related model, we should warn that the user
            // is trying to eager load an invalid model.
            throw new EagerLoaderException('Referenced key was not found on child model.');
        }

        // We only want to object ids to string to give
        // us some flexibility on array indexes.
        // any other type of ids should remain the same.
        if ($id instanceof ObjectId) {
            $id = (string) $id;
        }

        $this->addIdFor($eagerLoadKey, $id);
    }

    /**
     * Gets the referenced key from model. Defaults to _id.
     */
    private function extractKeyFrom(array $eagerLoadModel): string
    {
        return $eagerLoadModel['key'] ?? '_id';
    }
}
