<?php
namespace Mongolid\Model\Relations;

interface RelationInterface
{
    /**
     * Get the results of the relation.
     *
     * @return mixed
     */
    public function getResults();
}
