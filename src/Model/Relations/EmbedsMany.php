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
        $this->remove($model);

        $fieldValue = $this->parent->{$this->field};
        $fieldValue[] = $model;
        $this->parent->{$this->field} = array_values($fieldValue);
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
        $embeddedKey = $this->getKey($model);

        foreach ((array) $this->parent->{$this->field} as $arrayKey => $document) {
            if ($embeddedKey == $this->getKey($document)) {
                unset($this->parent->{$this->field}[$arrayKey]);
            }
        }

        $this->parent->{$this->field} = array_values((array) $this->parent->{$this->field});
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

        return new EmbeddedCursor($items);
    }
}
