<?php
namespace Mongolid\Model\Relations;

class EmbedsOne extends EmbedsMany
{
    /**
     * Cached result.
     *
     * @var mixed
     */
    private $document;

    public function remove($entity = null): void
    {
        $this->removeAll();
    }

    public function &getResults()
    {
        if (!$this->pristine()) {
            $items = (array) $this->parent->{$this->field};

            if (!empty($items) && !array_key_exists(0, $items)) {
                $items = [$items];
            }

            $this->document = $this->createCursor($items)->first();
            $this->pristine = true;
        }

        return $this->document;
    }
}
