<?php
namespace Mongolid\Model\Relations;

use Mongolid\Container\Ioc;
use Mongolid\Cursor\CursorFactory;

class EmbedsOne extends EmbedsMany
{
    public function remove($entity = null)
    {
        $this->removeAll();
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
