<?php
namespace Mongolid\Model\Relations;

use Mongolid\Model\ModelInterface;

class EmbedsOne extends AbstractRelation
{
    public function add(ModelInterface $model): void
    {
        $this->parent->{$this->field} = $model;
        $this->pristine = false;
    }

    public function remove(): void
    {
        unset($this->parent->{$this->field});
        $this->pristine = false;
    }

    /**
     * @return ModelInterface|null
     */
    public function get()
    {
        return $this->parent->{$this->field};
    }
}
