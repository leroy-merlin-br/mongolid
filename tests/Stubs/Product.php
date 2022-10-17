<?php
namespace Mongolid\Tests\Stubs;

use Mongolid\LegacyRecord;
use Mongolid\Model\Relations\ReferencesOne;

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
        'shop' => [
            'key' => 'skus.shop_id',
            'model' => Shop::class,
        ],
    ];

    public function price()
    {
        return $this->referencesOne(Price::class, '_id');
    }

    public function skus()
    {
        return $this->embedsMany(Sku::class, 'skus');
    }
}
