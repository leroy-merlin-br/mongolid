<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\LegacyRecord;

class Product extends LegacyRecord
{
    /**
     * @var string
     */
    protected $collection = 'products';

    public $with = [
        'price' => [
            'key' => '_id',
            'model' => Price::class
        ],
        'stock' => [
            'key' => '_id',
            'foreignKey' => 'product_id',
            'model' => Stock::class,
        ],
        'shop' => [
            'key' => 'skus.shop_id',
            'model' => Shop::class,
        ],
    ];

    public function price()
    {
        return $this->referencesOne(Price::class, '_id');
    }

    public function stock()
    {
        return $this->referencesOne(Stock::class, '_id', true, 'product_id');
    }

    public function skus()
    {
        return $this->embedsMany(Sku::class, 'skus');
    }
}
