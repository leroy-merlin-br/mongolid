<?php
namespace Mongolid\Cursor;

use Iterator;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Query\EagerLoader\Extractor;
use Mongolid\Util\CacheComponentInterface;

/**
 * This class is responsible for caching all related models
 * from a previous query in order to avoid the N+1 problem.
 * It also has a limit of models that can be cached
 * for performance reasons.
 */
class Cache
{
    public function cache(Iterator $models, array $eagerLoadModels = []): void
    {
        if ($this->shouldNotCache($models, $eagerLoadModels)) {
            return;
        }

        /** @var CacheComponentInterface $cacheComponent */
        $cacheComponent = Container::make(CacheComponentInterface::class);
        $extractor = new Extractor($eagerLoadModels);

        // Loops through all models returned by the previous query
        // and cache all related ids. These ids can come from
        // embedded models or referenced models.
        foreach ($models as $model) {
            // The model received here may be either an array from mongodb
            // or a cached model as a serialized array.
            // That's why we always will force an array to be used here.
            // It will ensure that we are only working with model arrays.
            $model = (array) $model;
            $extractor->extractFrom($model);
        }

        // With all models ids in hand. We need to query mongodb
        // to get all models instances and cache it into
        // our cache component.
        foreach ($extractor->getRelatedModels() as $loadModel) {
            $model = new $loadModel['model'];
            $ids = array_values($loadModel['ids']);
            $query = ['_id' => ['$in' => $ids]];

            foreach ($model->where($query) as $relatedModel) {
                $cacheKey = $this->generateCacheKey($relatedModel);
                $cacheComponent->put($cacheKey, $relatedModel, 36);
            }
        }
    }

    /**
     * Generates a unique cache key for the cursor in its current state.
     */
    protected function generateCacheKey(ModelInterface $model): string
    {
        $id = $model->_id;
        if ($id instanceof ObjectId) {
            $id = (string) $id;
        }

        return sprintf('%s:%s', $model->getCollectionName(), $id);
    }

    private function shouldNotCache(Iterator $models, array $eagerLoadModels): bool
    {
        return empty($eagerLoadModels)
            || !count($models);
    }
}
