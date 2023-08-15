<?php

namespace Mongolid\Model;

trait SoftDeletesTrait
{
    public $enabledSoftDeletes = true;

    public function isTrashed(): bool
    {
        return !is_null($this->{$this->getDeletedAtColumn()});
    }

    public function restore(): bool
    {
        $collumn = $this->getDeletedAtColumn();

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

    private function getDeletedAtColumn(): string
    {
        return defined(
            static::class . '::DELETED_AT'
        )
            ? static::DELETED_AT
            : 'deleted_at';
    }
}
