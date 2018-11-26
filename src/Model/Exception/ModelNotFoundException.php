<?php
namespace Mongolid\Model\Exception;

use RuntimeException;

/**
 * Model could not be found.
 */
class ModelNotFoundException extends RuntimeException
{
    /**
     * Exception message.
     *
     * @var string
     */
    protected $message = 'No query results for model';

    /**
     * Name of the affected Mongolid model.
     *
     * @var string
     */
    protected $model;

    /**
     * Set the affected Mongolid model.
     *
     * @param string $model name of the model
     *
     * @return $this
     */
    public function setModel(string $model)
    {
        $this->model = $model;

        $this->message = "No query results for model [{$model}].";

        return $this;
    }

    /**
     * Get the affected Mongolid model.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }
}
