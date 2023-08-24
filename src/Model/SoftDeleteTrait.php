<?php

namespace Mongolid\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Util\QueryBuilder;

trait SoftDeleteTrait
{
    public bool $isSoftDeleteEnabled = true;

    public function isTrashed(): bool
    {
        return !is_null($this->{QueryBuilder::getDeletedAtColumn($this)});
    }

    public function restore(): bool
    {
        $collumn = QueryBuilder::getDeletedAtColumn($this);

        if (!$this->isTrashed()) {
            return false;
        }

        unset($this->{$collumn});

        return $this->update();
    }

    public function forceDelete(): bool
    {
        return $this->execute('delete');
    }

    public function executeSoftDelete(): bool
    {
        $deletedAtColumn = QueryBuilder::getDeletedAtColumn($this);
        $this->$deletedAtColumn = new UTCDateTime(new DateTime('now'));

        return $this->update();
    }

    public static function withTrashed(
        mixed $query = [],
        array $projection = [],
        bool $useCache = false
    ): CursorInterface {
        return self::performSearch($query, $projection, $useCache);
    }

    private static function searchWithDataMapper(mixed $query, array $projection, bool $useCache): CursorInterface
    {
        $mapper = self::getDataMapperInstance();

        $mapper->withTrashed = true;

        return $mapper->where($query, $projection, $useCache);
    }

    private static function searchWithBuilder(mixed $query, array $projection, bool $useCache): CursorInterface
    {
        $mapper = self::getBuilderInstance();

        $mapper->withTrashed = true;

        return $mapper->where(
            new static(),
            $query,
            $projection,
            $useCache
        );
    }

    private static function performSearch(mixed $query, array $projection, bool $useCache): CursorInterface
    {
        $isLegacy = method_exists(self::class, 'getDataMapperInstance');

        return $isLegacy ?
            self::searchWithDataMapper($query, $projection, $useCache) :
            self::searchWithBuilder($query, $projection, $useCache);
    }
}
