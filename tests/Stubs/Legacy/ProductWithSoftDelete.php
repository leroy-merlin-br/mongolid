<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\Model\SoftDeletesTrait;

class ProductWithSoftDelete extends Product
{
    use SoftDeletesTrait;
}
