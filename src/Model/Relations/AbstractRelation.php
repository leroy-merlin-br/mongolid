<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;

abstract class AbstractRelation implements RelationInterface
{
    protected bool $pristine = false;

    /**
     * Cached results.
     */
    protected mixed $results;

    protected string $key = '_id';

    public function __construct(
        protected ModelInterface $parent,
        protected string $model,
        protected string $field
    ){
    }

    /**
     * Retrieve Relation Results.
     */
    abstract public function get(): mixed;

    /**
     * Retrieve cached Relation Results.
     */
    public function &getResults(): mixed
    {
        if (!$this->pristine()) {
            $this->results = $this->get();
            $this->pristine = true;
        }

        return $this->results;
    }

    protected function pristine(): bool
    {
        return $this->pristine;
    }

    /**
     * Gets the key of the given model. If there is no key,
     * a new key will be generated and set on the model (while still returning it).
     *
     * @param ModelInterface $model the object that the key will be retrieved from
     */
    protected function getKey(ModelInterface $model): mixed
    {
        if (!$model->{$this->key}) {
            $model->{$this->key} = new ObjectId();
        }

        return $model->{$this->key};
    }
}
