<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Ioc;
use Mongolid\Model\HasAttributesInterface;
use Mongolid\Util\ObjectIdUtils;

class ReferencesMany extends AbstractRelation
{
    /**
     * @var HasAttributesInterface
     */
    protected $entityInstance;

    /**
     * @var string
     */
    protected $key;

    public function __construct(
        HasAttributesInterface $parent,
        string $entity,
        string $field,
        string $key
    ) {
        parent::__construct($parent, $entity, $field);
        $this->key = $key;
        $this->documentEmbedder->setKey($key);
        $this->entityInstance = Ioc::make($this->entity);
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param mixed $entity model instance or _id to be referenced
     */
    public function attach($entity): void
    {
        $this->documentEmbedder->attach($this->parent, $this->field, $entity);
        $this->pristine = false;
    }

    /**
     * Attach many documents at once.
     *
     * @param array $entities model
     */
    public function attachMany(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->attach($entity);
        }
    }

    /**
     * Replace attached documents.
     *
     * @param array $entities
     */
    public function replace(array $entities): void
    {
        $this->detachAll();
        $this->attachMany($entities);
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $entity from inside the given $field.
     *
     * @param mixed $entity document, model instance or _id that have been referenced by $field
     */
    public function detach($entity): void
    {
        $this->documentEmbedder->detach($this->parent, $this->field, $entity);
        $this->pristine = false;
    }

    /**
     * Removes all document references from relation.
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

        return $this->entityInstance->where([$this->key => ['$in' => array_values($referencedKeys)]]);
    }
}
