<?php
namespace Mongolid\Model\Relations;

use Mongolid\Container\Ioc;
use Mongolid\Cursor\CursorFactory;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Cursor\EmbeddedCursor;

class EmbedsMany extends AbstractRelation
{
    /**
     * Cached results.
     *
     * @var EmbeddedCursor
     */
    private $cursor;

    /**
     * Embed a new document to an attribute. It will also generate an
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

    public function &getResults()
    {
        if (!$this->pristine()) {
            $items = (array) $this->parent->{$this->field};

            if (!empty($items) && !array_key_exists(0, $items)) {
                $items = [$items];
            }

            $this->cursor = $this->createCursor($items);
            $this->pristine = true;
        }

        return $this->cursor;
    }

    protected function createCursor($items): CursorInterface
    {
        return Ioc::make(CursorFactory::class)
            ->createEmbeddedCursor($this->entity, $items);
    }
}
