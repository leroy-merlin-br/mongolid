<?php

namespace Mongolid\Schema;

interface HasSchemaInterface
{
    /**
     * Returns a Schema object that describes an Entity in MongoDB.
     *
     * @return Schema
     */
    public function getSchema(): Schema;
}
