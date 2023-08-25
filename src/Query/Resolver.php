<?php

namespace Mongolid\Query;

use MongoDB\BSON\ObjectId;
use Mongolid\Model\ModelInterface;
use Mongolid\Util\ObjectIdUtils;

class Resolver
{
    public static function resolveQuery(mixed $query, ModelInterface $model, bool $ignoreSoftDelete = false): array
    {
        $query = self::prepareIdForQueryCompatibility($query);

        if (!$model->isSoftDeleteEnabled ?? false) {
            return $query;
        }

        if ($ignoreSoftDelete) {
            return $query;
        }

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

    private static function prepareIdForQueryCompatibility(mixed $query): array
    {
        if (!is_array($query)) {
            $query = ['_id' => $query];
        }

        if (
            isset($query['_id']) &&
            is_string($query['_id']) &&
            ObjectIdUtils::isObjectId($query['_id'])
        ) {
            $query['_id'] = new ObjectId($query['_id']);
        }

        if (
            isset($query['_id']) &&
            is_array($query['_id'])
        ) {
            $query['_id'] = self::convertStringIdsToObjectIds($query['_id']);
        }

        return $query;
    }

    private static function addSoftDeleteFilterIfRequired(array $query, ModelInterface $model): array
    {
        $field = self::getDeletedAtColumn($model);

        return array_merge(
            $query,
            [
                $field => ['$exists' => false],
            ]
        );
    }

    private static function convertStringIdsToObjectIds(array $query): array
    {
        foreach (['$in', '$nin'] as $operator) {
            if (
                self::verifyIdsNeedConversion($query, $operator)
            ) {
                $query[$operator] = self::convertIdsToObjects(
                    $query[$operator]
                );
            }
        }

        return $query;
    }

    private static function convertIdsToObjects(array $ids): array
    {
        foreach ($ids as $index => $id) {
            if (ObjectIdUtils::isObjectId($id)) {
                $ids[$index] = new ObjectId($id);
            }
        }

        return $ids;
    }

    private static function verifyIdsNeedConversion(array $query, string $operator): bool
    {
        return isset($query[$operator]) && is_array($query[$operator]);
    }
}
