<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;

class Product extends AbstractModel
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $collection = 'products';
}
