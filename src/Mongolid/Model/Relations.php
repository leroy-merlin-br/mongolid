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
     * @param  bool   $cachable
     *
     * @return mixed
     */
    protected function referencesOne($entity, $field, $cachable = true)
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

        return $entity::first(['_id' => $referenced_id]);
    }
    /**
     * Returns the cursor for the referenced documents as objects
     *
     * @param Model  $entity
     * @param string $field
     * @param bool   $cachable
     *
     * @return array
     */
    protected function referencesMany($entity, $field, $cachable = true)
    {
        $referenced_id = $this->$field;

        if (is_array($referenced_id) && isset($referenced_id[0])) {
            $referenced_id = $referenced_id[0];
        }

        if (is_subclass_of($entity, Schema::class)) {
            $dataMapper = Ioc::make(DataMapper::class);
            $dataMapper->schema = new $entity;
            return $dataMapper->where(['_id' => $referenced_id]);
        }

        return $entity::where(['_id' => $referenced_id]);
    }

    /**
     * Return a embedded documents as object
     *
     * @param string $entityName
     * @param string $field
     *
     * @return Model|null
     */
    protected function embedsOne($entity, $field)
    {
        if (is_subclass_of($entity, Schema::class)) {
            $entity = (new $entity)->entityClass;
        }

        return (new EmbeddedCursor($entity, [$this->$field]))->first();
    }

    /**
     * Return array of embedded documents as objects
     *
     * @param string $entity
     * @param string $field
     *
     * @return array Array with the embedded documents
     */
    protected function embedsMany($entity, $field)
    {
        if (is_subclass_of($entity, Schema::class)) {
            $entity = (new $entity)->entityClass;
        }

        return new EmbeddedCursor($entity, $this->$field);
    }
}
