<?php
namespace Mongolid\Model\Relations;

class EmbedsOne extends EmbedsMany
{
    public function add($entity): void
    {
        $this->removeAll();
        parent::add($entity);
    }

    public function remove($entity = null): void
    {
        $this->removeAll();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $items = (array) $this->parent->{$this->field};

        if (!empty($items) && !array_key_exists(0, $items)) {
            $items = [$items];
        }

        return $this->createCursor($items)->first();
    }
}
