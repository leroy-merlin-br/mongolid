<?php

namespace Mongolid\Model;

use Mongolid\Schema;
use Mongolid\Container\Ioc;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Cursor\EmbeddedCursor;

/**
 * It is supossed to be used in model classes in general
 *
 * @package  Mongolid
 */
trait Relations
{
    /**
     * Returns the referenced documents as objects
     *
     * @param  string $entity  Class of the entity or of the schema of the entity
     * @param  string $field
     *
     * @return mixed
     */
    protected function referencesOne(string $entity, string $field)
    {
        $referenced_id = $this->$field;

        if (is_array($referenced_id) && isset($referenced_id[0])) {
            $referenced_id = $referenced_id[0];
        }

        if (is_subclass_of($entity, Schema::class)) {
            $dataMapper = Ioc::make(DataMapper::class);
            $dataMapper->schema = new $entity;
            return $dataMapper->first(['_id' => $referenced_id]);
        }

        return Ioc::make($entity)->first(['_id' => $referenced_id]);
    }
    /**
     * Returns the cursor for the referenced documents as objects
     *
     * @param string $entity Class of the entity or of the schema of the entity
     * @param string $field
     *
     * @return array
     */
    protected function referencesMany(string $entity, string $field)
    {
        $referencedIds = $this->$field;
        $query = ['_id' => ['$in' => $referencedIds]];

        if (is_subclass_of($entity, Schema::class)) {
            $dataMapper = Ioc::make(DataMapper::class);
            $dataMapper->schema = new $entity;
            return $dataMapper->where($query);
        }

        return $entity::where($query);
    }

    /**
     * Return a embedded documents as object
     *
     * @param string $entity Class of the entity or of the schema of the entity
     * @param string $field
     *
     * @return Model|null
     */
    protected function embedsOne(string $entity, string $field)
    {
        if (is_subclass_of($entity, Schema::class)) {
            $entity = (new $entity)->entityClass;
        }

        return (new EmbeddedCursor($entity, (array)$this->$field))->first();
    }

    /**
     * Return array of embedded documents as objects
     *
     * @param string $entity Class of the entity or of the schema of the entity
     * @param string $field
     *
     * @return array Array with the embedded documents
     */
    protected function embedsMany(string $entity, string $field)
    {
        if (is_subclass_of($entity, Schema::class)) {
            $entity = (new $entity)->entityClass;
        }

        return new EmbeddedCursor($entity, $this->$field);
    }

    /**
     * Embed a new document to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field
     * @param mixed  $obj   document or model instance
     *
     * @return void
     */
    public function embed($field, &$obj)
    {
        $embeder = new DocumentEmbedder;
        $embeder->embed($this, $field, $obj);
    }

    /**
     * Removes an embedded document from the given field. It does that by using
     * the _id of the given $obj.
     *
     * @param string $field
     * @param mixed  $obj   document or model instance
     *
     * @return void
     */
    public function unembed($field, &$obj)
    {
        $embeder = new DocumentEmbedder;
        $embeder->unembed($this, $field, $obj);
    }

    /**
     * Attach document _id reference to an attribute. It will also generate an
     * _id for the document if it's not present.
     *
     * @param string $field
     * @param mixed  $obj   document or model instance to be referenced
     *
     * @return void
     */
    public function attach($field, &$obj)
    {
        $embeder = new DocumentEmbedder;
        $embeder->attach($this, $field, $obj);
    }

    /**
     * Removes a document _id reference from an attribute. It will remove the
     * _id of the given $obj from inside the given $field.
     *
     * @param string $field
     * @param mixed  $obj   document or model instance that have been referenced by $field
     *
     * @return void
     */
    public function detach($field, &$obj)
    {
        $embeder = new DocumentEmbedder;
        $embeder->detach($this, $field, $obj);
    }
}
