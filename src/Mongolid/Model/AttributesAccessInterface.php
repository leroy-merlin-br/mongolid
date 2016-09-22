<?php

namespace Mongolid\Model;

/**
 * This interface declares attribute getter, setters and also a useful
 * `fill` method.
 *
 * It is supposed to be used in conjunction with Attributes trait
 *
 * @see Attributes
 */
interface AttributesAccessInterface
{
    /**
     * Get an attribute from the model.
     *
     * @param string $key The attribute to be accessed.
     *
     * @return mixed
     */
    public function getAttribute(string $key);

    /**
     * Get all attributes from the model.
     *
     * @return mixed
     */
    public function getAttributes();

    /**
     * Set the model attributes using an array.
     *
     * @param array $input The data that will be used to fill the attributes.
     * @param bool  $force Force fill.
     *
     * @return void
     */
    public function fill(array $input, bool $force = false);

    /**
     * Set a given attribute on the model.
     *
     * @param string $key Name of the attribute to be unset.
     *
     * @return void
     */
    public function cleanAttribute(string $key);

    /**
     * Set a given attribute on the model.
     *
     * @param string $key   Name of the attribute to be set.
     * @param mixed  $value Value to be set.
     *
     * @return void
     */
    public function setAttribute(string $key, $value);

    /**
     * Stores original attributes from actual data from attributes
     * to be used in future comparisons about changes.
     *
     * Ideally should be called once right after retrieving data from
     * the database.
     *
     * @return void
     */
    public function syncOriginalAttributes();
}
