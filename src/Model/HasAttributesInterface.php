<?php

namespace Mongolid\Model;

/**
 * This interface declares attribute getter, setters and also a useful
 * `fill` method.
 *
 * It is supposed to be used in conjunction with Attributes trait
 *
 * @see HasAttributesTrait
 */
interface HasAttributesInterface
{
    /**
     * Set the model attributes using an array.
     * Notice: Even though the $object (if passed) hold the changes for simple cases,
     * you should always prefer to relay on the returned object, as it might change
     * when using polymorphable models.
     *
     * @param array                       $input  the data that will be used to fill the attributes
     * @param bool                        $force  force fill (ignore fillable fields)
     *
     * @see PolymorphableModelInterface
     */
    public function fill(array $input, bool $force = false);

    /**
     * Check if an attribute is set on the model.
     *
     * @param string $key the attribute to be checked
     */
    public function hasAttribute(string $key): bool;

    /**
     * Get an attribute from the model.
     *
     * @param string $key the attribute to be accessed
     *
     * @return mixed
     */
    public function getAttribute(string $key);

    /**
     * Get all attributes from the model.
     */
    public function getAttributes(): array;

    /**
     * Unset a given attribute on the model.
     *
     * @param string $key name of the attribute to be unset
     */
    public function cleanAttribute(string $key);

    /**
     * Set a given attribute on the model.
     *
     * @param string $key   name of the attribute to be set
     * @param mixed  $value value to be set
     */
    public function setAttribute(string $key, $value);

    /**
     * Stores original attributes from actual data from attributes
     * to be used in future comparisons about changes.
     *
     * Ideally should be called once right after retrieving data from
     * the database.
     */
    public function syncOriginalAttributes();

    /**
     * Retrieve original attributes.
     */
    public function getOriginalAttributes(): array;
}
