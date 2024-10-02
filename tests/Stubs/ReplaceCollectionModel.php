<?php
namespace Mongolid\Tests\Stubs;

use MongoDB\Collection;
use Mongolid\Model\AbstractModel;

class ReplaceCollectionModel extends AbstractModel
{
    protected bool $timestamps = false;

    protected ?string $collection = 'models';

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
