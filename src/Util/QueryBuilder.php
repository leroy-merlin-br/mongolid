<?php

namespace Mongolid\Util;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;

class QueryBuilder
{
    public static function resolveQuery(int|array|string $query, ModelInterface $model): array
    {
        $query = self::prepareValueQuery($query);

        return self::addSoftDeleteFilterIfRequired($query, $model);
    }

    public static function getDeletedAtColumn(ModelInterface $model): string
    {
        return defined(
            $model::class . '::DELETED_AT'
        )
            ? $model::DELETED_AT
            : 'deleted_at';
    }

    /**
     * Transforms a value that is not an array into an MongoDB query (array).
     * This method will take care of converting a single value into a query for
     * an _id, including when a objectId is passed as a string.
     */
    public static function prepareValueQuery(int|array|string $value): array
    {
        if (!is_array($value)) {
            $value = ['_id' => $value];
        }

        if (
            isset($value['_id']) &&
            is_string($value['_id']) &&
            ObjectIdUtils::isObjectId($value['_id'])
        ) {
            $value['_id'] = new ObjectId($value['_id']);
        }

        if (
            isset($value['_id']) &&
            is_array($value['_id'])
        ) {
            $value['_id'] = self::prepareArrayFieldOfQuery($value['_id']);
        }

        return $value;
    }

    private static function addSoftDeleteFilterIfRequired(array $query, ModelInterface $model): array
    {
        $field = self::getDeletedAtColumn($model);

        if (isset($query['withTrashed'])) {
            unset($query['withTrashed']);

            return $query;
        }

        return array_merge(
            $query,
            [
                $field => ['$exists' => false],
            ]
        );
    }

    /**
     * Prepares an embedded array of an query. It will convert string ObjectIds
     * in operators into actual objects.
     */
    private static function prepareArrayFieldOfQuery(array $value): array
    {
        foreach (['$in', '$nin'] as $operator) {
            if (
                isset($value[$operator]) &&
                is_array($value[$operator])
            ) {
                foreach ($value[$operator] as $index => $id) {
                    if (ObjectIdUtils::isObjectId($id)) {
                        $value[$operator][$index] = new ObjectId($id);
                    }
                }
            }
        }

        return $value;
    }
}
