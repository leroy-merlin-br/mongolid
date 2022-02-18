<?php

namespace Mongolid;

use Mongolid\Model\AbstractModel;

class LegacyRecord extends AbstractModel
{
    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field field to where the $obj will be embedded
     * @param mixed  $obj   document or model instance
     */
    public function embed(string $field, $obj)
    {
        $relation = $this->$field();

        $relation->add($obj);
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of the given $obj.
     *
     * @param string $field name of the field where the $obj is embeded
     * @param mixed  $obj   document, model instance or _id
     */
    public function unembed(string $field, $obj)
    {
        $relation = $this->$field();

        $relation->remove($obj);
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field name of the field where the reference will be stored
     * @param mixed  $obj   document, model instance or _id to be referenced
     */
    public function attach(string $field, $obj)
    {
        $relation = $this->$field();

        $relation->attach($obj);
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $obj from inside the given $field.
     *
     * @param string $field field where the reference is stored
     * @param mixed  $obj   document, model instance or _id that have been referenced by $field
     */
    public function detach(string $field, $obj)
    {
        $relation = $this->$field();

        $relation->detach($obj);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param mixed $method     name of the method that is being called
     * @param mixed $parameters parameters of $method
     *
     * @throws BadMethodCallException in case of invalid methods be called
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $value = $parameters[0] ?? null;

        // Alias to attach
        if ('attachTo' == substr($method, 0, 8)) {
            $field = lcfirst(substr($method, 8));

            return $this->attach($field, $value);
        }

        // Alias to embed
        if ('embedTo' == substr($method, 0, 7)) {
            $field = lcfirst(substr($method, 7));

            return $this->embed($field, $value);
        }

        throw new BadMethodCallException(
            sprintf(
                'The following method can not be reached or does not exist: %s@%s',
                get_class($this),
                $method
            )
        );
    }
}
