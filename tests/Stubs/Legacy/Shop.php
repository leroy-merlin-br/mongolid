<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\LegacyRecord;

class Shop extends LegacyRecord
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $collection = 'shops';
}
