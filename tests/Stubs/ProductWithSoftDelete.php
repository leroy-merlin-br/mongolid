<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\SoftDeleteTrait;

class ProductWithSoftDelete extends Product
{
    use SoftDeleteTrait;
}
