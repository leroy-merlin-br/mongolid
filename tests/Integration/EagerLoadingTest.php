<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Tests\Stubs\Price;
use Mongolid\Tests\Stubs\Product;
use Mongolid\Tests\Stubs\ReferencedUser;
use Mongolid\Tests\Stubs\Shop;
use Mongolid\Tests\Stubs\Sku;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

final class EagerLoadingTest extends IntegrationTestCase
{
    public function testShouldEagerLoadingAllPrices(): void
    {
        Container::instance(CacheComponentInterface::class, new CacheComponent);
        $this->createProductWithPrice('Playstation', 299.90);
        $this->createProductWithPrice('Xbox', 199.90);
        $this->createProductWithPrice('Switch', 399.90);

        $cursor = Product::all();

        dd($cursor->first()->price());

//        foreach ($cursor as $product) {
//            dump(['felix',   $product->price->value, (string) $product->price->_id]);
//        }

//        $this->assertInstanceOf(Price::class, $product->price);
//        $this->assertSame($product->price->value, 299.90);
    }

    public function testShouldEagerLoadingAllShops(): void
    {
        $cache = Container::instance(CacheComponentInterface::class, new CacheComponent);
        $product1 = $this->createProductWithPrice('Playstation', 299.90);
        $product2 = $this->createProductWithPrice('Xbox', 199.90);
        $product3 = $this->createProductWithPrice('Switch', 399.90);

        $sku1 = $this->createSkuWithShop('Playstation');
        $sku2 = $this->createSkuWithShop('Xbox Product');
        $sku3 = $this->createSkuWithShop('Nintendo Product');
        $sku4 = $this->createSkuWithShop('Another Nintendo Product');

        $product1->embed('skus', $sku1);
        $product2->embed('skus', $sku2);
        $product3->embed('skus', $sku3);
        $product3->embed('skus', $sku4);

        $this->assertTrue($product1->save());
        $this->assertTrue($product2->save());
        $this->assertTrue($product3->save());

        $cursor = Product::where([], [], true);

        foreach ($cursor as $product) {
            echo 'Product: ' .$product->name . PHP_EOL;
            echo 'Price: ' .$product->price()->value . PHP_EOL;
            foreach ($product->skus() as $sku) {
                echo 'SKU: ' .$sku->name . PHP_EOL;
                echo 'SKU Name: ' .$sku->shop()->name . PHP_EOL;
            }
        };
    }

    private function createProductWithPrice(string $name, float $price): Product
    {
        // Product
        $product = new Product();
        $product->name = $name;
        $this->assertTrue($product->save());
        $this->createPriceFor($product, $price);

        return $product;
    }

    private function createPriceFor(Product $product, float $priceValue): Price
    {
        $price = new Price();
        $price->_id = $product->_id;
        $price->value = $priceValue;
        $this->assertTrue($price->save());

        return $price;
    }

    private function createShop(string $shopName): Shop
    {
        $shop = new Shop();
        $shop->name = $shopName;
        $this->assertTrue($shop->save());

        return $shop;
    }

    private function createSkuWithShop(string $productName): Sku
    {
        $shop = $this->createShop("$productName Shop");

        $sku = new Sku();
        $sku->name = $productName;
        $sku->shop_id = $shop->_id;

        return $sku;
    }
}
