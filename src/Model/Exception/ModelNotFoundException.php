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
     */
    protected $message = 'No query results for model';

    /**
     * Name of the affected Mongolid model.
     */
    protected string $model;

    /**
     * Set the affected Mongolid model.
     *
     * @param string $model name of the model
     */
    public function setModel(string $model): self
    {
        $this->model = $model;

        $this->message = "No query results for model [{$model}].";

        return $this;
    }

    /**
     * Get the affected Mongolid model.
     */
    public function getModel(): string
    {
        return $this->model;
    }
}
