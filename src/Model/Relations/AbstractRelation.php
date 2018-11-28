<?php
namespace Mongolid\Model\Relations;

use Mongolid\Container\Ioc;
use Mongolid\Model\HasAttributesInterface;

abstract class AbstractRelation implements RelationInterface
{
    /**
     * @var HasAttributesInterface
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
     * @var DocumentEmbedder
     */
    protected $documentEmbedder;

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

    public function __construct(HasAttributesInterface $parent, string $model, string $field)
    {
        $this->parent = $parent;
        $this->model = $model;
        $this->field = $field;

        $this->documentEmbedder = Ioc::make(DocumentEmbedder::class);
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
}
