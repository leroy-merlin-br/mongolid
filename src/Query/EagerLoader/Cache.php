<?php
namespace Mongolid\Cursor;

use Iterator;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Query\EagerLoader\Extractor;
use Mongolid\Util\CacheComponentInterface;

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

        foreach ($models as $model) {
            // Convert to array to make sure the methods
            // and objects are always equals.
            $model = (array) $model;
            $extractor->extractFrom($model);
        }

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

    private function shouldNotCache(Iterator $models, array $eagerLoadModels): bool
    {
        return empty($eagerLoadModels)
            || !count($models);
    }

    public function extractFromEmbeddedModel(array $model, string $key): array
    {
        list($method, $attribute) = explode('.', $key);
        foreach ($model[$method] ?? [] as $embeddedModel) {
            $ids = array_merge(
                $ids ?? [],
                $this->extractFromModel($embeddedModel, $attribute)
            );
        }

        return $ids ?? [];
    }

    private function extractFromModel(array $model, string $key): array
    {
        $id = $model[$key];
        if ($id instanceof ObjectId) {
            $id = (string) $id;
        }

        return [$id => $id];
    }
}
