<?php

namespace Mongolid\Schema;

/**
 * The DynamicSchema will accept additional fields that are not specified in
 * the $schema property. This is useful if you does not have a clear idea
 * of how the document will look like.
 */
class DynamicSchema extends Schema
{
    /**
     * {@inheritdoc}
     */
    public $dynamic = true;
}
