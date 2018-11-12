<?php
namespace Mongolid\Model\Relations;

use Mongolid\Container\Ioc;
use Mongolid\Cursor\CursorFactory;

class EmbedsMany extends AbstractRelation
{
    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param mixed $entity model
     */
    public function add($entity)
    {
        $this->documentEmbedder->embed($this->parent, $this->field, $entity);
        $this->parent->unsetRelation($this->relationName);
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of given $entity.
     *
     * @param mixed $entity model or _id
     */
    public function remove($entity)
    {
        $this->documentEmbedder->unembed($this->parent, $this->field, $entity);
        $this->parent->unsetRelation($this->relationName); // TODO better implementation of cache invalidation
    }

    public function removeAll()
    {
        unset($this->parent->{$this->field});
        $this->parent->unsetRelation($this->relationName);
    }

    public function getResults()
    {
        $items = (array) $this->parent->{$this->field};

        if (!empty($items) && !array_key_exists(0, $items)) {
            $items = [$items];
        }

        return Ioc::make(CursorFactory::class)
            ->createEmbeddedCursor($this->entity, $items);
    }
}
