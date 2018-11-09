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
        $referencedId = $this->parent->{$this->field};

        if (is_array($referencedId) && isset($referencedId[0])) {
            $referencedId = $referencedId[0];
        }

        if (ObjectIdUtils::isObjectId($referencedId)) {
            $referencedId = new ObjectId((string) $referencedId);
        }

        return $this->entityInstance->first(
            ['_id' => $referencedId],
            [],
            $this->cacheable
        );
    }
}
