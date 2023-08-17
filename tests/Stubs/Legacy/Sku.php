<?php
namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\LegacyRecord;

class Sku extends LegacyRecord
{
    public function shop()
    {
        return $this->referencesOne(Shop::class, 'shop_id');
    }
}
