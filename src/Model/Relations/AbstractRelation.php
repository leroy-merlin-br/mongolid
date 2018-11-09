<?php
namespace Mongolid\Model\Relations;

use Mongolid\Container\Ioc;
use Mongolid\Model\DocumentEmbedder;
use Mongolid\Model\HasAttributesInterface;

abstract class AbstractRelation
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

    public function __construct(HasAttributesInterface $parent, string $entity, string $field)
    {
        $this->relationName = $this->guessRelationName();
        $this->parent = $parent;
        $this->entity = $entity;
        $this->field = $field;

        $this->documentEmbedder = Ioc::make(DocumentEmbedder::class);
    }

    abstract public function getResults();

    /**
     * @return mixed
     */
    private function guessRelationName()
    {
        $functionName = __FUNCTION__;

        return collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))
            ->pluck('function')
            ->first(
                function ($value) use ($functionName) {
                    return !in_array(
                        $value,
                        [$functionName, '__construct', 'referencesOne', 'referencesMany', 'embedsOne', 'embedsMany']
                    );
                }
            );
    }
}
