<?php

namespace Mongolid\DataMapper;

use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Schema\Schema;
use Mongolid\Model\PolymorphableModelInterface;

/**
 * EntityAssembler have the responsibility of assembling the data coming from
 * the database into actual entities. Since the entities need to be assembled
 * whenever they are being built with data from the database, from a cursor or
 * from an embedded field, this service is a reusable tool to turn field array of
 * attributes into the actual Entity.
 *
 * This class is meant to do the opposite of the SchemaMapper.
 *
 * @see http://martinfowler.com/eaaCatalog/dataTransferObject.html
 */
class EntityAssembler
{
    /**
     * Builds an object from the provided data.
     *
     * @param array|object $document the attributes that will be used to compose the entity
     * @param Schema       $schema   schema that will be used to map each field
     *
     * @return mixed
     */
    public function assemble($document, Schema $schema)
    {
        $entityClass = $schema->entityClass;
        $model = Container::make($entityClass);

        foreach ($document as $field => $value) {
            $fieldType = $schema->fields[$field] ?? null;

            if ($fieldType && 'schema.' == substr($fieldType, 0, 7)) {
                $value = $this->assembleDocumentsRecursively($value, substr($fieldType, 7));
            }

            $model->$field = $value;
        }

        $entity = $this->morphingTime($model);

        return $this->prepareOriginalAttributes($entity);
    }

    /**
     * Returns the return of polymorph method of the given entity if available.
     *
     * @see \Mongolid\Model\PolymorphableInterface::polymorph
     * @see https://i.ytimg.com/vi/TFGN9kAjdis/maxresdefault.jpg
     *
     * @param mixed $entity the entity that may or may not have a polymorph method
     *
     * @return mixed the result of $entity->polymorph or the $entity itself
     */
    protected function morphingTime(ModelInterface $entity)
    {
        if ($entity instanceof PolymorphableModelInterface) {
            $class = $entity->polymorph($entity->getDocumentAttributes());

            if ($class !== get_class($entity)) {
                $originalAttributes = $entity->getDocumentAttributes();
                $entity = Container::make($class);
                $entity->fill($originalAttributes, true);
            }
        }

        return $entity;
    }

    /**
     * Stores original attributes from Entity if needed.
     *
     * @param mixed $entity the entity that may have the attributes stored
     *
     * @return mixed the entity with original attributes
     */
    protected function prepareOriginalAttributes($entity)
    {
        if ($entity instanceof ModelInterface) {
            $entity->syncOriginalDocumentAttributes();
        }

        return $entity;
    }

    /**
     * Assembly multiple documents for the given $schemaClass recursively.
     *
     * @param mixed  $value       a value of an embeded field containing entity data to be assembled
     * @param string $schemaClass the schemaClass to be used when assembling the entities within $value
     *
     * @return mixed
     */
    protected function assembleDocumentsRecursively($value, string $schemaClass)
    {
        $value = (array) $value;

        if (empty($value)) {
            return;
        }

        $schema = Container::make($schemaClass);
        $assembler = Container::make(self::class);

        if (!isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $key => $subValue) {
            $value[$key] = $assembler->assemble($subValue, $schema);
        }

        return $value;
    }
}
