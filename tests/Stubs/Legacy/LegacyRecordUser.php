<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\Cursor\CursorInterface;
use Mongolid\LegacyRecord;

class LegacyRecordUser extends LegacyRecord
{
    /**
     * @var bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    public $mutable = true;

    /**
     * @var bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    public $dynamic = false;

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $collection = 'users';

    /**
     * @var bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $timestamps = true;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];

    public function siblings(): CursorInterface
    {
        return $this->embedsMany(LegacyRecordUser::class, 'siblings');
    }

    /**
     * @return CursorInterface|array
     */
    public function grandsons()
    {
        /** @var CursorInterface|array $grandsons */
        $grandsons = $this->referencesMany(LegacyRecordUser::class, 'grandsons');

        return $grandsons;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function setSecretAttribute(mixed $_value): string
    {
        return 'password_override';
    }
}
