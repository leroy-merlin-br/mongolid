<?php
namespace Mongolid\Model\Relations;

use Mongolid\Model\ModelInterface;

class EmbedsOne extends EmbedsMany
{
    public function add(ModelInterface $model): void
    {
        $this->removeAll();
        parent::add($model);
    }

    public function remove(ModelInterface $model = null): void
    {
        $this->removeAll();
    }

    /**
     * @return ModelInterface|null
     */
    public function get()
    {
        $items = $this->parent->{$this->field} ?? [];

        if (is_object($items)) {
            return $items;
        }

        return $this->createCursor($items)->first();
    }
}
