<?php

namespace Mongolid\Model\Relations;

interface RelationInterface
{
    /**
     * Get the results of the relation.
     */
    public function getResults(): mixed;
}
