<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\LegacyRecord;

class Sku extends LegacyRecord
{
    public function shop(): ?Shop
    {
        return $this->referencesOne(Shop::class, 'shop_id');
    }
}
