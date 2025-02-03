<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\AbstractModel;

class Product extends AbstractModel
{
    protected ?string $collection = 'products';
}
