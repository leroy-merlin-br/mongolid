<?php
namespace Mongolid\Model\Relations;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;

abstract class AbstractRelation implements RelationInterface
{
    /**
     * @var ModelInterface
     */
    protected $parent;

    /**
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var bool
     */
    protected $pristine = false;

    /**
     * Cached results.
     *
     * @var mixed
     */
    protected $results;

    /**
     * @var string
     */
    protected $key = '_id';

    public function __construct(ModelInterface $parent, string $model, string $field)
    {
        $this->parent = $parent;
        $this->model = $model;
        $this->field = $field;
    }

    /**
     * Retrieve Relation Results.
     *
     * @return mixed
     */
    abstract public function get();

    /**
     * Retrieve cached Relation Results.
     *
     * @return mixed
     */
    public function &getResults()
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
     *
     * @return ObjectId|mixed
     */
    protected function getKey(ModelInterface $model)
    {
        if (!$model->{$this->key}) {
            $model->{$this->key} = new ObjectId();
        }

        return $model->{$this->key};
    }
}
