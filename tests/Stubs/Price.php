<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;

class Price extends AbstractModel
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $collection = 'prices';
}
