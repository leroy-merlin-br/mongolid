<?php

namespace Mongolid\Util;

use Mongolid\Model\ModelInterface;

class SoftDeleteQueries
{
    public static function insertFilterForSoftDelete(array $query, ModelInterface $model): array
    {
        $field = self::getDeletedAtColumn($model);

        return array_merge(
            $query,
            [
                '$or' => [
                    [
                        $field => null,
                    ],
                    [
                        $field => ['$exists' => false],
                    ],
                ],
            ]
        );
    }

    public static function getDeletedAtColumn(ModelInterface $model): string
    {
        return defined(
            $model::class . '::DELETED_AT'
        )
            ? $model::DELETED_AT
            : 'deleted_at';
    }
}
