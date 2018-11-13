<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Util\ObjectIdUtils;

class ReferencesOne extends ReferencesMany
{
    /**
     * Cached result
     *
     * @var mixed
     */
    private $document;

    public function detach($entity = null): void
    {
        $this->detachAll();
    }

    public function &getResults()
    {
        if (!$this->pristine()) {
            $referencedKey = $this->parent->{$this->field};

            if (is_array($referencedKey) && isset($referencedKey[0])) {
                $referencedKey = $referencedKey[0];
            }

            if (ObjectIdUtils::isObjectId($referencedKey)) {
                $referencedKey = new ObjectId((string) $referencedKey);
            }

            $this->document = $this->entityInstance->first(
                [$this->key => $referencedKey],
                [],
                $this->cacheable
            );
            $this->pristine = true;
        }

        return $this->document;
    }
}
