<?php

namespace Mongolid\Model;

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
     * @param      $model
     * @param      $field
     * @param bool $cachable
     *
     * @return
     */
    protected function referencesOne($model, $field, $cachable = true)
    {

    }
    /**
     * Returns the cursor for the referenced documents as objects
     *
     * @param Model  $model
     * @param string $field
     * @param bool   $cachable
     *
     * @return array
     */
    protected function referencesMany($model, $field, $cachable = true)
    {

    }
    /**
     * Return a embedded documents as object
     *
     * @param string $modelName
     * @param string $field
     *
     * @return Model|null
     */
    protected function embedsOne($modelName, $field)
    {

    }
    /**
     * Return array of embedded documents as objects
     *
     * @param string $model
     * @param string $field
     *
     * @return array Array with the embedded documents
     */
    protected function embedsMany($model, $field)
    {

    }
}
