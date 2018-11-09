<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Ioc;
use Mongolid\Model\HasAttributesInterface;
use Mongolid\Util\ObjectIdUtils;

class ReferencesMany extends AbstractRelation
{
    /**
     * @var bool
     */
    protected $cacheable;

    /**
     * @var HasAttributesInterface
     */
    protected $entityInstance;

    public function __construct(HasAttributesInterface $parent, string $entity, string $field, bool $cacheable = true)
    {
        parent::__construct($parent, $entity, $field);
        $this->cacheable = $cacheable;
        $this->entityInstance = Ioc::make($this->entity);
    }
    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param mixed $entity model instance or _id to be referenced
     */
    public function attach($entity)
    {
        $this->documentEmbedder->attach($this->parent, $this->field, $entity);
        $this->parent->unsetRelation($this->relationName);
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $entity from inside the given $field.
     *
     * @param mixed $entity document, model instance or _id that have been referenced by $field
     */
    public function detach($entity)
    {
        $this->documentEmbedder->detach($this->parent, $this->field, $entity);
        $this->parent->unsetRelation($this->relationName);
    }

    /**
     * Removes all document references from relation.
     */
    public function detachAll()
    {
        unset($this->parent->{$this->field});
        $this->parent->unsetRelation($this->relationName);
    }

    public function getResults()
    {
        $referencedIds = (array) $this->parent->{$this->field};

        if (ObjectIdUtils::isObjectId($referencedIds[0] ?? '')) {
            foreach ($referencedIds as $key => $value) {
                $referencedIds[$key] = new ObjectId((string) $value);
            }
        }

        return $this->entityInstance->where(
            ['_id' => ['$in' => array_values($referencedIds)]],
            [],
            $this->cacheable
        );
    }
}
