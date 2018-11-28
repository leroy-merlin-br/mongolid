<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\ObjectIdUtils;

class ReferencesMany extends AbstractRelation
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

    /**
     * Attach model reference. It will also generate an
     * _id for the model if it's not present.
     */
    public function attach(ModelInterface $model): void
    {
        $referencedKey = $this->getKey($model);
        $fieldValue = (array) $this->parent->{$this->field};

        foreach ($fieldValue as $key) {
            if ($key == $referencedKey) {
                return;
            }
        }

        $fieldValue[] = $referencedKey;
        $this->parent->{$this->field} = array_values($fieldValue);
        $this->pristine = false;
    }

    /**
     * Attach many models at once.
     *
     * @param ModelInterface[] $entities
     */
    public function attachMany(array $entities): void
    {
        foreach ($entities as $model) {
            $this->attach($model);
        }
    }

    /**
     * Replace attached documents.
     *
     * @param ModelInterface[] $entities
     */
    public function replace(array $entities): void
    {
        $this->detachAll();
        $this->attachMany($entities);
    }

    /**
     * Removes model reference from an attribute.
     */
    public function detach(ModelInterface $model): void
    {
        $referencedKey = $this->getKey($model);

        foreach ((array) $this->parent->{$this->field} as $arrayKey => $documentKey) {
            if ($documentKey == $referencedKey) {
                unset($this->parent->{$this->field}[$arrayKey]);
                $this->parent->{$this->field} = array_values((array) $this->parent->{$this->field});
                $this->pristine = false;
                return;
            }
        }
    }

    /**
     * Removes all model references from relation.
     */
    public function detachAll(): void
    {
        unset($this->parent->{$this->field});
        $this->pristine = false;
    }

    public function get()
    {
        $referencedKeys = (array) $this->parent->{$this->field};

        if (ObjectIdUtils::isObjectId($referencedKeys[0] ?? '')) {
            foreach ($referencedKeys as $key => $value) {
                $referencedKeys[$key] = new ObjectId((string) $value);
            }
        }

        return $this->modelInstance->where([$this->key => ['$in' => array_values($referencedKeys)]]);
    }
}
