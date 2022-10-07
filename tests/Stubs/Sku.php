<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\LegacyRecord;
use Mongolid\Model\Relations\ReferencesOne;

class Sku extends LegacyRecord
{
    public function shop()
    {
        return $this->referencesOne(Shop::class, 'shop_id');
    }
}
