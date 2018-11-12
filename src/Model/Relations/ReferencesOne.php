<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Util\ObjectIdUtils;

class ReferencesOne extends ReferencesMany
{
    public function detach($entity = null)
    {
        $this->detachAll();
    }

    public function getResults()
    {
        $referencedKey = $this->parent->{$this->field};

        if (is_array($referencedKey) && isset($referencedKey[0])) {
            $referencedKey = $referencedKey[0];
        }

        if (ObjectIdUtils::isObjectId($referencedKey)) {
            $referencedKey = new ObjectId((string) $referencedKey);
        }

        return $this->entityInstance->first(
            [$this->key => $referencedKey],
            [],
            $this->cacheable
        );
    }
}
