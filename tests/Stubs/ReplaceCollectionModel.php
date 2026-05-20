<?php

namespace Mongolid\Tests\Stubs;

use MongoDB\Collection;
use Mongolid\Model\AbstractModel;

class ReplaceCollectionModel extends AbstractModel
{
    /**
     * {@inheritdoc}
     */
    protected $timestamps = false;

    /**
     * {@inheritdoc}
     */
    protected $collection = 'models';

    protected Collection $rawCollection;

    public function setCollection(Collection $collection): void
    {
        $this->rawCollection = $collection;
    }

    public function getCollection(): Collection
    {
        return $this->rawCollection;
    }
}
