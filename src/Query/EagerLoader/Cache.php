<?php

namespace Mongolid\Query\EagerLoader;

use Mongolid\Container\Container;
use Mongolid\Util\CacheComponentInterface;

class Cache
{
    use CacheKeyGeneratorTrait;

    /**
     * @var CacheComponentInterface
     */
    private $cacheComponent;

    public function __construct(CacheComponentInterface $cacheComponent)
    {
        $this->cacheComponent = $cacheComponent;
    }

    public function cache(array $eagerLoadedModel): void
    {
        $model = Container::make($eagerLoadedModel['model']);
        $ids = array_values($eagerLoadedModel['ids'] ?? []);

        // By default, the foreign key will always be the _id, but
        // you can override this behavior by setting it on your
        // model on the eager load settings.
        $foreignKey = $eagerLoadedModel['foreignKey'] ?? '_id';

        // In case there is no IDs, means that either we don't
        // have any related models or models was not configured
        // correctly, in both case, we should not cache it.
        if (empty($ids)) {
            return;
        }

        $query = [$foreignKey => ['$in' => $ids]];
        $count = 0;

        foreach ($model->where($query) as $relatedModel) {
            if ($count++ >= EagerLoader::DOCUMENT_LIMIT) {
                break;
            }

            $cacheKey = $this->generateCacheKey($relatedModel);
            $this->cacheComponent->put($cacheKey, $relatedModel, 36);
        }
    }
}
