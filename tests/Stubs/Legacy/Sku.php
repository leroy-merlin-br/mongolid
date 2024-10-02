<?php
namespace Mongolid\Tests\Stubs\Legacy;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mongolid\LegacyRecord;

class Sku extends LegacyRecord
{
    /**
     * @throws BindingResolutionException
     */
    public function shop(): mixed
    {
        return $this->referencesOne(Shop::class, 'shop_id');
    }
}
