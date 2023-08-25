<?php

namespace Mongolid\Model;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Query\Resolver;

trait SoftDeleteTrait
{
    public bool $isSoftDeleteEnabled = true;

    public function isTrashed(): bool
    {
        return !is_null($this->{Resolver::getDeletedAtColumn($this)});
    }

    public function restore(): bool
    {
        $collumn = Resolver::getDeletedAtColumn($this);

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
        $deletedAtColumn = Resolver::getDeletedAtColumn($this);
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
        return self::getDataMapperInstance()
            ->withoutSoftDelete()
            ->where($query, $projection, $useCache);
    }

    private static function searchWithBuilder(mixed $query, array $projection, bool $useCache): CursorInterface
    {
        return self::getBuilderInstance()
            ->withoutSoftDelete()
            ->where(new static(), $query, $projection, $useCache);
    }

    private static function performSearch(mixed $query, array $projection, bool $useCache): CursorInterface
    {
        $isLegacy = method_exists(self::class, 'getDataMapperInstance');

        return $isLegacy ?
            self::searchWithDataMapper($query, $projection, $useCache) :
            self::searchWithBuilder($query, $projection, $useCache);
    }
}
