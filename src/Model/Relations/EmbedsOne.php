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
        $items = (array) $this->parent->{$this->field};

        if (!empty($items) && !array_key_exists(0, $items)) {
            $items = [$items];
        }

        return $this->createCursor($items)->first();
    }
}
