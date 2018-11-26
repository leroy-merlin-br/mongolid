<?php
namespace Mongolid\Model\Relations;

use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\Model\ModelInterface;

class EmbedsMany extends AbstractRelation
{
    /**
     * Embed a new document. It will also generate an
     * _id for the document if it's not present.
     */
    public function add(ModelInterface $model): void
    {
        $this->documentEmbedder->embed($this->parent, $this->field, $model);
        $this->pristine = false;
    }

    /**
     * Embed many documents at once.
     *
     * @param array $entities model
     */
    public function addMany(array $entities): void
    {
        foreach ($entities as $model) {
            $this->add($model);
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
     * the _id of given $model.
     *
     * @param mixed $model model or _id
     */
    public function remove(ModelInterface $model): void
    {
        $this->documentEmbedder->unembed($this->parent, $this->field, $model);
        $this->pristine = false;
    }

    public function removeAll(): void
    {
        unset($this->parent->{$this->field});
        $this->pristine = false;
    }

    /**
     * @return EmbeddedCursor
     */
    public function get()
    {
        $items = $this->parent->{$this->field} ?? [];

        if (is_object($items)) {
            $items = [$items];
        }

        return $this->createCursor($items);
    }

    protected function createCursor(array $items): EmbeddedCursor
    {
        return new EmbeddedCursor($items);
    }
}
