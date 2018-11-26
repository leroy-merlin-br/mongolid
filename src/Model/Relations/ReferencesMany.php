<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Ioc;
use Mongolid\Model\HasAttributesInterface;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\ObjectIdUtils;

class ReferencesMany extends AbstractRelation
{
    /**
     * @var HasAttributesInterface
     */
    protected $modelInstance;

    /**
     * @var string
     */
    protected $key;

    public function __construct(
        HasAttributesInterface $parent,
        string $model,
        string $field,
        string $key
    ) {
        parent::__construct($parent, $model, $field);
        $this->key = $key;
        $this->documentEmbedder->setKey($key);
        $this->modelInstance = Ioc::make($this->model);
    }

    /**
     * Attach model reference. It will also generate an
     * _id for the model if it's not present.
     */
    public function attach(ModelInterface $model): void
    {
        $this->documentEmbedder->attach($this->parent, $this->field, $model);
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
        $this->documentEmbedder->detach($this->parent, $this->field, $model);
        $this->pristine = false;
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
