<?php
namespace Mongolid\Tests\Stubs\Legacy;

use Illuminate\Contracts\Container\BindingResolutionException;
use Mongolid\Cursor\CursorInterface;
use Mongolid\LegacyRecord;
use Mongolid\Tests\Stubs\Price;

class Product extends LegacyRecord
{
    protected ?string $collection = 'products';

    public array $with = [
        'price' => [
            'key' => '_id',
            'model' => Price::class
        ],
        'shop' => [
            'key' => 'skus.shop_id',
            'model' => Shop::class,
        ],
    ];

    /**
     * @throws BindingResolutionException
     */
    public function price(): mixed
    {
        return $this->referencesOne(Price::class, '_id');
    }

    public function skus(): CursorInterface
    {
        return $this->embedsMany(Sku::class, 'skus');
    }
}
