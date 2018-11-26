<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\ObjectIdUtils;

class ReferencesOne extends ReferencesMany
{
    public function attach(ModelInterface $model): void
    {
        $this->detachAll();
        parent::attach($model);
    }

    public function detach(ModelInterface $model = null): void
    {
        $this->detachAll();
    }

    public function get()
    {
        $referencedKey = $this->parent->{$this->field};

        if (is_array($referencedKey) && isset($referencedKey[0])) {
            $referencedKey = $referencedKey[0];
        }

        if (ObjectIdUtils::isObjectId($referencedKey)) {
            $referencedKey = new ObjectId((string) $referencedKey);
        }

        return $this->modelInstance->first([$this->key => $referencedKey]);
    }
}
