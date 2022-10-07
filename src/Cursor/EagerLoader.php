<?php
namespace Mongolid\Cursor;

use ArrayIterator;
use Iterator;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\CacheComponentInterface;

class EagerLoader
{
    public function where(Iterator $models, array $eagerLoadModels = []): Iterator
    {
        $documents = [];
        $relatedModels = [];

        if (empty($eagerLoadModels)) {
            return $models;
        }

        if (!count($models)) {
            return $models;
        }

        /** @var CacheComponentInterface $cacheComponent */
        $cacheComponent = Container::make(CacheComponentInterface::class);

        foreach ($models as $model) {
            // Convert to array to make sure the methods
            // and objects are always equals.
            $model = (array) $model;
            $documents[] = $model;
            foreach ($eagerLoadModels as $eagerLoadKey => $loadModel) {
                $key = $loadModel['key'] ?? '_id';
                if ($this->keyHasDot($key)) {
                    $extractedDots = explode('.', $key);
                    $method = $extractedDots[0];
                    $attribute = $extractedDots[1];
                    foreach ($model[$method] ?? [] as $sku) {
                        $id = $sku[$attribute];
                        if ($id instanceof ObjectId) {
                            $id = (string) $id;
                        }
                        $eagerLoadModels[$eagerLoadKey]['ids'][$id] = $id;
                    }
                } else {
                    $id = $model[$key];
                    $eagerLoadModels[$eagerLoadKey]['ids'][] = $id;
                }
            }
        }

        foreach ($eagerLoadModels as $eagerLoadKey => $loadModel) {
            $model = new $loadModel['model'];
            $ids = array_values($loadModel['ids']);
            $query = ['_id' => ['$in' => $ids]];

            foreach ($model->where($query) as $relatedModel) {
                $id = $relatedModel->_id;
                if ($id instanceof ObjectId) {
                    $id = (string) $id;
                }
                $cacheKey = $this->generateCacheKey($relatedModel);
                $cacheComponent->put($cacheKey, $relatedModel, 36);
                $relatedModels[$eagerLoadKey][$id][] = $relatedModel;
            }
        }

        return $models;

//        $newDocuments = [];
//
//        foreach ($documents as $document) {
//            foreach ($eagerLoadModels as $eagerLoadKey => $loadModel) {
//                $key = $loadModel['key'];
//                if ($this->keyHasDot($key)) {
//                    $extractedDots = explode('.', $key);
//                    $method = $extractedDots[0];
//                    $attribute = $extractedDots[1];
//                    $newEmbedded = [];
//                    foreach ($document[$method] ?? [] as $sku) {
//                        $id = $sku[$attribute];
//                        if ($id instanceof ObjectId) {
//                            $id = (string) $id;
//                        }
//                        $skuKey = "eager_loaded_{$eagerLoadKey}";
//                        $sku[$skuKey] = $relatedModels[$eagerLoadKey][$id];
//                        $newEmbedded[] = $sku;
//                    }
//                    $document[$method] = $newEmbedded;
//                } else {
//                    $id = $document[$key];
//                    if ($id instanceof ObjectId) {
//                        $id = (string) $id;
//                    }
//                    $relatedModelKey = "eager_loaded_{$eagerLoadKey}";
//                    $document[$relatedModelKey] = $relatedModels[$eagerLoadKey][$id];
//                }
//            }
//            $newDocuments[] = $document;
//        }

        return new ArrayIterator($newDocuments);
    }

    private function keyHasDot($key)
    {
        return str_contains($key, '.');
    }

    /**
     * Generates an unique cache key for the cursor in it's current state.
     *
     * @return string cache key to identify the query of the current cursor
     */
    protected function generateCacheKey(ModelInterface $model): string
    {
        $id = $model->_id;
        if ($id instanceof ObjectId) {
            $id = (string) $id;
        }

        return sprintf('%s:%s', $model->getCollectionName(), $id);
    }
}
