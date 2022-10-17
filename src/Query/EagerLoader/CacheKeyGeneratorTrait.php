<?php

namespace Mongolid\Query\EagerLoader;

use Mongolid\Model\ModelInterface;

trait CacheKeyGeneratorTrait
{
    public function generateCacheKey(ModelInterface $model, string $id = null): string
    {
        if (is_null($id)) {
            $id = (string) $model->_id;
        }

        return sprintf('%s:%s', $model->getCollectionName(), $id);
    }
}
