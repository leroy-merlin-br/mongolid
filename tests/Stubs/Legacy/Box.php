<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\LegacyRecord;
use Mongolid\Tests\Stubs\Size;

class Box extends LegacyRecord
{
    /**
     * @var string
     */
    protected $collection = 'boxes';

    protected array $casts = [
        'box_size' => Size::class,
    ];
}
