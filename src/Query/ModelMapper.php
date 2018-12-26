<?php
namespace Mongolid\Query;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Container\Container;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\ObjectIdUtils;

/**
 * The ModelMapper will map an object or an array of data to a MongoDB's document.
 * When calling 'map' the model will have the data formatted and some fields might change.
 */
class ModelMapper
{
    /**
     * Maps model to a document to be inserted on database.
     */
    public function map(ModelInterface $model, array $allowedFields, bool $dynamic, bool $timestamps): array
    {
        $this->clearNullFields($model);
        $this->clearDynamicFields($model, $allowedFields, $dynamic, $timestamps);
        $this->manageTimestamps($model, $timestamps);
        $this->manageId($model);

        return $model->getDocumentAttributes();
    }

    /**
     * If the model is not dynamic, remove all non specified fields.
     */
    protected function clearDynamicFields(
        ModelInterface $model,
        array $allowedFields,
        bool $dynamic,
        bool $timestamps
    ): void {
        if ($dynamic) {
            return;
        }

        $merge = ['_id'];

        if ($timestamps) {
            $merge[] = 'created_at';
            $merge[] = 'updated_at';
        }

        $allowedFields = array_unique(array_merge($allowedFields, $merge));

        foreach ($model->getDocumentAttributes() as $field => $value) {
            if (!in_array($field, $allowedFields)) {
                unset($model->{$field});
            }
        }
    }

    private function clearNullFields(ModelInterface $model): void
    {
        foreach ($model->getDocumentAttributes() as $field => $value) {
            if (null === $value) {
                unset($model->{$field});
            }
        }
    }

    private function manageTimestamps(ModelInterface $model, bool $timestamps): void
    {
        if (!$timestamps) {
            return;
        }
        $model->updated_at = Container::make(UTCDateTime::class, ['milliseconds' => null]);

        if (!$model->created_at instanceof UTCDateTime) {
            $model->created_at = $model->updated_at;
        }
    }

    private function manageId(ModelInterface $model)
    {
        $value = $model->_id;

        if (is_null($value) || (is_string($value) && ObjectIdUtils::isObjectId($value))) {
            $value = Container::make(ObjectId::class, ['id' => $value]);
        }

        $model->_id = $value;
    }
}
