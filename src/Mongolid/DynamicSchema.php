<?php
namespace Mongolid;

/**
 * The DynamicSchema will accept additional fields that are not specified in
 * the $schema property. This is usefull if you doesn't have a clear idea of how
 * tthe document will look like
 *
 * @package  Mongolid
 */
class DynamicSchema extends Schema
{
    /**
     * {@inheritsdoc}
     */
    protected $dynamic = true;
}
