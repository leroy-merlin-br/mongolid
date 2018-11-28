<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\ObjectIdUtils;

class ReferencesOne extends AbstractRelation
{
    /**
     * @var ModelInterface
     */
    protected $modelInstance;

    public function __construct(ModelInterface $parent, string $model, string $field, string $key)
    {
        parent::__construct($parent, $model, $field);
        $this->key = $key;
        $this->modelInstance = Container::make($this->model);
    }

    public function attach(ModelInterface $model): void
    {
        $this->parent->{$this->field} = $this->getKey($model);
        $this->pristine = false;
    }

    public function detach(): void
    {
        unset($this->parent->{$this->field});
        $this->pristine = false;
    }

    public function get()
    {
        if (!$referencedKey = $this->parent->{$this->field}) {
            return null;
        }

        if (is_string($referencedKey) && ObjectIdUtils::isObjectId($referencedKey)) {
            $referencedKey = new ObjectId($referencedKey);
        }

        return $this->modelInstance->first([$this->key => $referencedKey]);
    }
}
