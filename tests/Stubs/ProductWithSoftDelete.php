<?php

namespace Mongolid\Tests\Stubs;

use Mongolid\Model\SoftDeletesTrait;

class ProductWithSoftDelete extends Product
{
    use SoftDeletesTrait;
}
