<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;

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
     * Embeds the given $model into $field of $parent. This method will also
     * consider the key of the $model in order to update it if it is already
     * present in $field.
     *
     * @param ModelInterface       $parent the object where the $model will be embedded
     * @param string               $field  name of the field of the object where the document will be embedded
     * @param ModelInterface|array $model  model that will be embedded within $parent
     */
    public function embed(ModelInterface $parent, string $field, &$model): bool
    {
        // In order to update the document if it exists inside the $parent
        $this->unembed($parent, $field, $model);

        $fieldValue = $parent->$field;
        $fieldValue[] = $model;
        $parent->$field = array_values($fieldValue);

        return true;
    }

    /**
     * Removes the given $model from $field of $parent. This method will
     * consider the key of the $model in order to remove it.
     *
     * @param ModelInterface       $parent the object where the $model will be removed
     * @param string               $field  name of the field of the object where the document is
     * @param ModelInterface|array $model  model that will be removed from $parent
     */
    public function unembed(ModelInterface $parent, string $field, &$model): bool
    {
        $embeddedKey = $this->getKey($model);

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
     * @param ModelInterface       $parent the object where $model will be referenced
     * @param string               $field  the field where the key reference of $model will be stored
     * @param ModelInterface|array $model  the object that is being attached
     */
    public function attach(ModelInterface $parent, string $field, &$model): bool
    {
        $referencedKey = $this->getKey($model);

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
     * @param ModelInterface       $parent the object where $model reference will be removed
     * @param string               $field  the field where the key reference of $model is stored
     * @param ModelInterface|array $model  the object being detached or its key
     */
    public function detach(ModelInterface $parent, string $field, &$model): bool
    {
        $referencedKey = $this->getKey($model);

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
     * @param ModelInterface|array $object the object|array that the key will be retrieved from
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
