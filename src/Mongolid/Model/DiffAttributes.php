<?php
namespace Mongolid\Model;

class DiffAttributes
{
    public function generate(Model $instance)
    {
        $original = $instance->getOriginalAttributes();
        $actual   = $instance->getAttributes();

        $changed = [];

        $originalChanges = $this->getChangeOnOriginalAttributes($original, $actual);
        $newAttributes   = $this->getNewAttributes($original, $actual);

        return array_merge($originalChanges, $newAttributes);
    }

    public function getChangeOnOriginalAttributes(array $original, array $actual)
    {
        $changed = [];

        foreach ($original as $name => $value) {
            if (isset($actual[$name]) && $actual[$name] != $value) {
                $changed[$name] = $actual[$name];
            }
        }

        return $changed;
    }

    public function getNewAttributes(array $original, array $actual)
    {
        foreach ($actual as $name => $value) {
            if (! isset($original[$name]) && $value) {
                $changed[$name] = $actual[$name];
            }
        }
    }
}
