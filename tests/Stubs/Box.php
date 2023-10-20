<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;

class Box extends AbstractModel
{
    /**
     * @var string
     */
    protected $collection = 'boxes';

    protected array $casts = [
        'box_size' => Size::class,
    ];
}
