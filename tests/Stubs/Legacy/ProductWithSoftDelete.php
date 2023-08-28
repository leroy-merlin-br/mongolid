<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\Model\SoftDeleteTrait;

class ProductWithSoftDelete extends Product
{
    use SoftDeleteTrait;
}
