<?php
namespace Mongolid\Query\EagerLoader;

use Iterator;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\CacheComponentInterface;

/**
 * This class is responsible for caching all related models
 * from a previous query in order to avoid the N+1 problem.
 * It also has a limit of models that can be cached
 * for performance reasons.
 */
class EagerLoader
{
    /**
     * Limits the number of documents that should be
     * cached for performance reasons.
     */
    public const DOCUMENT_LIMIT = 100;

    public function cache(Iterator $models, array $eagerLoadModels = []): void
    {
        if ($this->shouldNotCache($models, $eagerLoadModels)) {
            return;
        }

        $extractor = new Extractor($eagerLoadModels);
        $cache = Container::make(Cache::class);
        $count = 0;

        // Loops through all models returned by the previous query
        // and cache all related ids. These ids can come from
        // embedded models or referenced models.
        foreach ($models as $model) {
            if ($count++ >= self::DOCUMENT_LIMIT) {
                break;
            }

            // The model received here may be either an array from mongodb
            // or a cached model as a serialized array.
            // That's why we always will force an array to be used here.
            // It will ensure that we are only working with model arrays.
            $model = (array) $model;
            $extractor->extractFrom($model);
        }

        // Loops through all eager loaded models to cache
        // related models using our cache component
        foreach ($extractor->getRelatedModels() as $loadModel) {
            $cache->cache($loadModel);
        }
    }

    private function shouldNotCache(Iterator $models, array $eagerLoadModels): bool
    {
        return empty($eagerLoadModels)
            || !count($models);
    }
}
