<?php

namespace Mongolid\Schema;

interface HasSchemaInterface
{
    /**
     * Returns a Schema object that describes an Entity in MongoDB.
     */
    public function getSchema(): Schema;
}
