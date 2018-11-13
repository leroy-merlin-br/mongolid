<?php
namespace Mongolid\Model;

use MongoDB\BSON\ObjectId;

/**
 * Document embedder is a service that will embed documents within each other.
 */
class DocumentEmbedder
{
    /**
     * @var string
     */
    private $key;

    public function __construct(string $key = '_id')
    {
        $this->setKey($key);
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * Embeds the given $entity into $field of $parent. This method will also
     * consider the key of the $entity in order to update it if it is already
     * present in $field.
     *
     * @param mixed  $parent the object where the $entity will be embedded
     * @param string $field  name of the field of the object where the document will be embedded
     * @param mixed  $entity entity that will be embedded within $parent
     */
    public function embed($parent, string $field, &$entity): bool
    {
        // In order to update the document if it exists inside the $parent
        $this->unembed($parent, $field, $entity);

        $fieldValue = $parent->$field;
        $fieldValue[] = $entity->getDocumentAttributes();
        $parent->$field = array_values($fieldValue);

        return true;
    }

    /**
     * Removes the given $entity from $field of $parent. This method will
     * consider the key of the $entity in order to remove it.
     *
     * @param mixed  $parent the object where the $entity will be removed
     * @param string $field  name of the field of the object where the document is
     * @param mixed  $entity entity that will be removed from $parent
     */
    public function unembed($parent, string $field, &$entity): bool
    {
        $embeddedKey = $this->getKey($entity);

        foreach ((array) $parent->$field as $arrayKey => $document) {
            if ($embeddedKey == $this->getKey($document)) {
                unset($parent->$field[$arrayKey]);
            }
        }

        $parent->$field = array_values((array) $parent->$field);

        return true;
    }

    /**
     * Attach a new key reference into $field of $parent.
     *
     * @param mixed        $parent the object where $entity will be referenced
     * @param string       $field  the field where the key reference of $entity will be stored
     * @param object|array $entity the object that is being attached
     */
    public function attach($parent, string $field, &$entity): bool
    {
        $referencedKey = $this->getKey($entity);

        foreach ((array) $parent->$field as $key) {
            if ($key == $referencedKey) {
                return true;
            }
        }

        $fieldValue = $parent->$field;
        $fieldValue[] = $referencedKey;
        $parent->$field = array_values($fieldValue);

        return true;
    }

    /**
     * Removes a key reference from $field of $parent.
     *
     * @param mixed  $parent the object where $entity reference will be removed
     * @param string $field  the field where the key reference of $entity is stored
     * @param mixed  $entity the object being detached or its key
     */
    public function detach($parent, string $field, &$entity): bool
    {
        $referencedKey = $this->getKey($entity);

        foreach ((array) $parent->$field as $arrayKey => $documentKey) {
            if ($documentKey == $referencedKey) {
                unset($parent->$field[$arrayKey]);
            }
        }

        $parent->$field = array_values((array) $parent->$field);

        return true;
    }

    /**
     * Gets the key of the given object or array. If there is no key in it a new
     * key will be generated and set on the object (while still returning it).
     *
     * @param mixed $object the object|array that the key will be retrieved from
     *
     * @return ObjectId|mixed
     */
    protected function getKey(&$object)
    {
        if (is_array($object)) {
            if (isset($object[$this->key]) && $object[$this->key]) {
                return $object[$this->key];
            }

            return $object[$this->key] = new ObjectId();
        }

        if (is_object($object) && !$object instanceof ObjectId) {
            if (isset($object->{$this->key}) && $object->{$this->key}) {
                return $object->{$this->key};
            }

            return $object->{$this->key} = new ObjectId();
        }

        return $object;
    }
}
