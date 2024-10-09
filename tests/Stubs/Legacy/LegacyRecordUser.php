<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\Cursor\CursorInterface;
use Mongolid\LegacyRecord;

class LegacyRecordUser extends LegacyRecord
{
    public bool $mutable = true;

    public bool $dynamic = false;

    protected ?string $collection = 'users';

    protected bool $timestamps = true;

    /**
     * @var string[]
     */
    protected array $fillable = [
        'name',
    ];

    public function siblings(): CursorInterface
    {
        return $this->embedsMany(LegacyRecordUser::class, 'siblings');
    }

    public function grandsons(): array
    {
        return $this->referencesMany(LegacyRecordUser::class, 'grandsons');
    }

    public function setSecretAttribute(): string
    {
        return 'password_override';
    }
}
