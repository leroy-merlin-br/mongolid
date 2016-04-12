<?php

namespace Mongolid\DataMapper;

use Mongolid\Container\Ioc;
use Mongolid\Schema;

/**
 * EntityAssembler have the responsability of assembling the data coming from
 * the database into actual entities. Since the entities need to be assembled
 * whenever they are being built with data from the database, from a cursor or
 * from an embeded field, this service is a reusable tool to turn field array of
 * attributes into the actual Entity.
 *
 * This class is meant to do the oposite of the SchemaMapper.
 *
 * @see http://martinfowler.com/eaaCatalog/dataTransferObject.html
 * @package Mongolid
 */
class EntityAssembler
{
    /**
     * Builds an object from the provided data
     *
     * @param array  $document The attributes that will be used to compose the entity.
     * @param Schema $schema   Schema that will be used to map each field.
     *
     * @return mixed
     */
    public function assemble(array $document, Schema $schema)
    {
        $entityClass = $schema->entityClass;
        $model       = Ioc::make($entityClass);

        foreach ($document as $field => $value) {
            $fieldType = $schema->fields[$field] ?? null;

            if ($fieldType && substr($fieldType, 0, 7) == 'schema.') {
                $value = $this->assembleDocumentsRecursivelly($value, substr($fieldType, 7));
            }

            $model->$field = $value;
        }

        return $model;
    }

    /**
     * Assembly multiple documents for the given $schemaClass recursivelly
     *
     * @param  mixed  $value       A value of an embeded field containing entity data to be assembled.
     * @param  string $schemaClass The schemaClass to be used when assembling the entities within $value.
     *
     * @return mixed
     */
    protected function assembleDocumentsRecursivelly($value, string $schemaClass)
    {
        $value = (array)$value;

        if (empty($value)) {
            return null;
        }

        $schema    = Ioc::make($schemaClass);
        $assembler = Ioc::make(EntityAssembler::class);

        if (! isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $key => $subValue) {
            $value[$key] = $assembler->assemble($subValue, $schema);
        }

        return $value;
    }
}
