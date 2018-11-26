<?php
namespace Mongolid\Query;

use Mongolid\Container\Ioc;
use Mongolid\Model\ModelInterface;
use Mongolid\Model\PolymorphableInterface;
use Mongolid\Schema\AbstractSchema;

/**
 * ModelAssembler have the responsibility of assembling the data coming from
 * the database into actual entities. Since the entities need to be assembled
 * whenever they are being built with data from the database, from a cursor or
 * from an embedded field, this service is a reusable tool to turn field array of
 * attributes into the actual Model.
 *
 * This class is meant to do the opposite of the SchemaMapper.
 *
 * @see http://martinfowler.com/eaaCatalog/dataTransferObject.html
 */
class ModelAssembler
{
    /**
     * Builds an object from the provided data.
     *
     * @param array|object   $document the attributes that will be used to compose the model
     * @param AbstractSchema $schema   schema that will be used to map each field
     */
    public function assemble($document, AbstractSchema $schema): ModelInterface
    {
        $modelClass = $schema->modelClass;
        $model = Ioc::make($modelClass);

        foreach ($document as $field => $value) {
            $fieldType = $schema->fields[$field] ?? null;

            if ($fieldType && 'schema.' == substr($fieldType, 0, 7)) {
                $value = $this->assembleDocumentsRecursively($value, substr($fieldType, 7));
            }

            $model->$field = $value;
        }

        $model = $this->morphingTime($model);

        return $this->prepareOriginalAttributes($model);
    }

    /**
     * Returns the return of polymorph method of the given model if available.
     *
     * @see \Mongolid\Model\PolymorphableInterface::polymorph
     * @see https://i.ytimg.com/vi/TFGN9kAjdis/maxresdefault.jpg
     *
     * @param mixed $model the model that may or may not have a polymorph method
     *
     * @return mixed the result of $model->polymorph or the $model itself
     */
    protected function morphingTime(ModelInterface $model): ModelInterface
    {
        if ($model instanceof PolymorphableInterface) {
            return $model->polymorph();
        }

        return $model;
    }

    /**
     * Stores original attributes from Model if needed.
     *
     * @param mixed $model the model that may have the attributes stored
     *
     * @return mixed the model with original attributes
     */
    protected function prepareOriginalAttributes(ModelInterface $model)
    {
        $model->syncOriginalDocumentAttributes();

        return $model;
    }

    /**
     * Assembly multiple documents for the given $schemaClass recursively.
     *
     * @param mixed  $value       a value of an embedded field containing model data to be assembled
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

        $schema = Ioc::make($schemaClass);
        $assembler = Ioc::make(static::class);

        if (!isset($value[0])) {
            $value = [$value];
        }

        foreach ($value as $key => $subValue) {
            $value[$key] = $assembler->assemble($subValue, $schema);
        }

        return $value;
    }
}
