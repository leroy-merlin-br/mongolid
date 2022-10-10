<?php
namespace Mongolid\Query\EagerLoader;

use ArrayIterator;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Cursor\SchemaCursor;
use Mongolid\Cursor\SchemaEmbeddedCursor;
use Mongolid\Query\EagerLoader\Exception\EagerLoaderException;
use Mongolid\TestCase;
use Mockery as m;
use Mongolid\Tests\Stubs\Price;
use Mongolid\Tests\Stubs\Product;
use Mongolid\Tests\Stubs\Shop;
use Mongolid\Util\CacheComponentInterface;

class ExtractorTest extends TestCase
{
    public function testShouldExtractIdFromModel(): void
    {
        // Set
        $eagerLoader = [
            'price' => [
                'key' => '_id',
                'model' => Price::class
            ],
            'shop' => [
                'key' => 'skus.shop_id',
                'model' => Shop::class,
            ],
        ];
        $extractor = new Extractor($eagerLoader);
        $product = new Product();
        $product->_id = 123;
        $product->skus = [
            [
                'name' => 'Playstation',
                'shop_id' => 12345
            ]
        ];
        $expected = [
            'price' => [
                'key' => '_id',
                'model' => 'Mongolid\Tests\Stubs\Price',
                'ids' => [
                    123 => 123,
                ],
            ],
            'shop' => [
                'key' => 'skus.shop_id',
                'model' => 'Mongolid\Tests\Stubs\Shop',
                'ids' => [
                    12345 => 12345,
                ],
            ],
        ];


        // Expectations

        // Actions
        $result = $extractor->extractFrom($product->toArray());

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldNotExtractIdFromAnInvalidEagerLoadConfig(): void
    {
        // Set
        $eagerLoader = [
            'price' => [
                'key' => 'unexistentReferencedId',
                'model' => Price::class
            ],
        ];
        $extractor = new Extractor($eagerLoader);
        $product = new Product();
        $product->_id = 123;
        $product->skus = [
            [
                'name' => 'Playstation',
                'shop_id' => 12345
            ]
        ];

        // Expectations
        $this->expectException(EagerLoaderException::class);

        // Actions
        $extractor->extractFrom($product->toArray());
    }
}
