<?php
namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\Cursor\CursorInterface;
use Mongolid\LegacyRecord;

class LegacyRecordUser extends LegacyRecord
{
    /**
     * @var string
     */
    protected $collection = 'users';

    public bool $mutable = true;

    /**
     * @var bool
     */
    protected $timestamps = true;

    /**
     * @var bool
     */
    public $dynamic = false;

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

    public function setSecretAttribute($value): string
    {
        return 'password_override';
    }
}
