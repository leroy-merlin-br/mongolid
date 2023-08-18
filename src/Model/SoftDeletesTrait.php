<?php

namespace Mongolid\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Util\QueryBuilder;

trait SoftDeletesTrait
{
    public bool $enabledSoftDeletes = true;
    public bool $forceDelete = false;

    public function isTrashed(): bool
    {
        return !is_null($this->{self::getDeletedAtColumn()});
    }

    public function restore(): bool
    {
        $collumn = self::getDeletedAtColumn();

        if (!$this->isTrashed()) {
            return false;
        }

        unset($this->{$collumn});

        return $this->execute('save');
    }

    public function forceDelete(): bool
    {
        $this->forceDelete = true;

        return $this->execute('delete');
    }

    public function executeSoftDelete(): bool
    {
        $deletedAtCoullum = QueryBuilder::getDeletedAtColumn($this);
        $this->$deletedAtCoullum = new UTCDateTime(new DateTime('now'));

        return $this->update();
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
