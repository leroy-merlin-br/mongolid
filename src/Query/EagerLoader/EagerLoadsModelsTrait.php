<?php

namespace Mongolid\Query\EagerLoader;

trait EagerLoadsModelsTrait
{
    /**
     * This attribute is used to eager load models for
     * referenced ids. You can eager load any children
     * models using this parameter. Every time this
     * model is queried, it will load its referenced
     * models together.
     *
     * @var array
     */
    protected $with = [];

    public function getEagerLoadedModels(): array
    {
        return $this->with ?? [];
    }
}
