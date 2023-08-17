<?php

namespace Mongolid\Model;

use Mongolid\Cursor\CursorInterface;
use Mongolid\Util\QueryBuilder;

trait SoftDeletesTrait
{
    public bool $enabledSoftDeletes = true;

    public function isTrashed(): bool
    {
        return !is_null($this->{self::getDeletedAtColumn()});
    }

    public function restore(): bool
    {
        $collumn = self::getDeletedAtColumn();

        if (!$this->{$collumn}) {
            return false;
        }

        $this->{$collumn} = null;

        return $this->execute('save');
    }

    public function forceDelete(): bool
    {
        $this->forceDelete = true;

        return $this->execute('delete');
    }

    public static function withTrashed(
        array|string|int $query = [],
        array $projection = [],
        bool $useCache = false
    ): CursorInterface {
        $query = QueryBuilder::prepareValueQuery($query);
        $query = array_merge($query, [
            'withTrashed' => true,
        ]);

        return parent::where($query, $projection, $useCache);
    }

    private static function getDeletedAtColumn(): string
    {
        return defined(
            static::class . '::DELETED_AT'
        )
            ? static::DELETED_AT
            : 'deleted_at';
    }
}
