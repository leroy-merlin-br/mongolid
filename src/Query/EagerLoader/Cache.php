<?php

namespace Mongolid\Query\EagerLoader;

use Mongolid\Container\Container;
use Mongolid\Util\CacheComponentInterface;

class Cache
{
    use CacheKeyGeneratorTrait;

    /**
     * Limits the number of documents that should be
     * cached for performance reasons.
     */
    private const DOCUMENT_LIMIT = 100;

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
        $ids = array_values($eagerLoadedModel['ids']);
        $query = ['_id' => ['$in' => $ids]];
        $count = 0;

        foreach ($model->where($query) as $relatedModel) {
            if ($count++ >= self::DOCUMENT_LIMIT) {
                break;
            }

            $cacheKey = $this->generateCacheKey($relatedModel);
            $this->cacheComponent->put($cacheKey, $relatedModel, 36);
        }
    }
}
