<?php
namespace Mongolid\Model\Relations;

use Mongolid\Container\Ioc;
use Mongolid\Model\DocumentEmbedder;
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
    protected $entity;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var DocumentEmbedder
     */
    protected $documentEmbedder;

    /**
     * @var string
     */
    protected $relationName;

    public function __construct(HasAttributesInterface $parent, string $entity, string $field, string $relationName)
    {
        $this->relationName = $relationName;
        $this->parent = $parent;
        $this->entity = $entity;
        $this->field = $field;

        $this->documentEmbedder = Ioc::make(DocumentEmbedder::class);
    }

    abstract public function getResults();
}
