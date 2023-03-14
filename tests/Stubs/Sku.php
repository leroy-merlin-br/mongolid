<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\LegacyRecord;

class Sku extends LegacyRecord
{
    public function shop()
    {
        return $this->referencesOne(Shop::class, 'shop_id');
    }
}
