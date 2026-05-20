<?php

namespace Mongolid\Tests\Stubs\Legacy;

use Mongolid\Cursor\CursorInterface;
use Mongolid\LegacyRecord;
use Mongolid\Tests\Stubs\Price;

class Product extends LegacyRecord
{
    /**
     * @var array<string, array{key: string, model: class-string}>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    public $with = [
        'price' => [
            'key' => '_id',
            'model' => Price::class,
        ],
        'shop' => [
            'key' => 'skus.shop_id',
            'model' => Shop::class,
        ],
    ];

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $collection = 'products';

    public function price(): ?Price
    {
        return $this->referencesOne(Price::class, '_id');
    }

    public function skus(): CursorInterface
    {
        return $this->embedsMany(Sku::class, 'skus');
    }
}
