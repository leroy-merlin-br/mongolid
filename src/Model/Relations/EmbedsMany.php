<?php
namespace Mongolid\Model\Relations;

use Mongolid\Cursor\EmbeddedCursor;

class EmbedsMany extends AbstractRelation
{
    /**
     * Embed a new document. It will also generate an
     * _id for the document if it's not present.
     *
     * @param mixed $entity model
     */
    public function add($entity): void
    {
        $this->documentEmbedder->embed($this->parent, $this->field, $entity);
        $this->pristine = false;
    }

    /**
     * Embed many documents at once.
     *
     * @param array $entities model
     */
    public function addMany(array $entities): void
    {
        foreach ($entities as $entity) {
            $this->add($entity);
        }
    }

    /**
     * Replace embedded documents.
     *
     * @param array $entities
     */
    public function replace(array $entities): void
    {
        $this->removeAll();
        $this->addMany($entities);
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of given $entity.
     *
     * @param mixed $entity model or _id
     */
    public function remove($entity): void
    {
        $this->documentEmbedder->unembed($this->parent, $this->field, $entity);
        $this->pristine = false;
    }

    public function removeAll(): void
    {
        unset($this->parent->{$this->field});
        $this->pristine = false;
    }

    public function get()
    {
        $items = (array) $this->parent->{$this->field};

        if (!empty($items) && !array_key_exists(0, $items)) {
            $items = [$items];
        }

        return $this->createCursor($items);
    }

    protected function createCursor($items): EmbeddedCursor
    {
        return new EmbeddedCursor($this->entity, $items);
    }
}
