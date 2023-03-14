<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Tests\Stubs\Price;
use Mongolid\Tests\Stubs\Product;
use Mongolid\Tests\Stubs\ReferencedUser;
use Mongolid\Tests\Stubs\Shop;
use Mongolid\Tests\Stubs\Sku;
use Mongolid\Tests\Stubs\Stock;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

final class EagerLoadingTest extends IntegrationTestCase
{
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

        $products = Product::where([], [], true);

        foreach ($products as $product) {
            // Call product price.
            $priceId = $product->price()->_id;
            $this->assertTrue($cache->has("prices:$priceId"));
            foreach ($product->skus() as $sku) {
                // Call shop on every sku instance.
                $shopId = $sku->shop()->_id;
                $this->assertTrue($cache->has("shops:$shopId"));
            }
        }
    }

    public function testShouldEagerStocksUsingForeignKeys(): void
    {
        $cache = Container::instance(CacheComponentInterface::class, new CacheComponent);
        $product1 = $this->createProductWithPrice('Playstation', 299.90);
        $product2 = $this->createProductWithPrice('Xbox', 199.90);
        $product3 = $this->createProductWithPrice('Switch', 399.90);

        $this->createStockFor($product1, 10);
        $this->createStockFor($product2, 20);
        $this->createStockFor($product3, 30);

        $this->assertTrue($product1->save());
        $this->assertTrue($product2->save());
        $this->assertTrue($product3->save());

        $products = Product::where([], [], true);

        foreach ($products as $product) {
            // Call product price.
            $priceId = $product->price()->_id;
            $stockId = $product->stock()->_id;
            $this->assertTrue($cache->has("prices:$priceId"));
            $this->assertTrue($cache->has("stocks:$stockId"));
        }
    }

    public function testShouldEagerLoadUsingIntegerAsKeys(): void
    {
        $cache = Container::instance(CacheComponentInterface::class, new CacheComponent);
        $product1 = $this->createProductWithPrice('Playstation', 299.90, 123);
        $product2 = $this->createProductWithPrice('Xbox', 199.90, 456);
        $product3 = $this->createProductWithPrice('Switch', 399.90, 789);

        $this->createStockFor($product1, 10);
        $this->createStockFor($product2, 20);
        $this->createStockFor($product3, 30);

        $this->assertTrue($product1->save());
        $this->assertTrue($product2->save());
        $this->assertTrue($product3->save());

        $products = Product::where([], [], true);

        foreach ($products as $product) {
            // Call product price.
            $priceId = $product->price()->_id;
            $stockId = $product->stock()->_id;
            $this->assertTrue($cache->has("prices:$priceId"));
            $this->assertTrue($cache->has("stocks:$stockId"));
        }
    }

    public function testShouldEagerLoadOnlyACertainLimitOfProducts(): void
    {
        $cache = Container::instance(CacheComponentInterface::class, new CacheComponent);
        $cacheLimit = 100;

        for ($i = 1; $i < 101; $i++) { // DOCUMENT_LIMIT+1
            $this->createProductWithPrice("Playstation $i", 100.0 + $i);
        }

        $products = Product::where([], [], true);

        $count = 0;
        foreach ($products as $product) {
            $count++;
            $hasCache = $count <= $cacheLimit;
            // Call product price.
            $priceId = $product->price()->_id;
            $this->assertSame($cache->has("prices:$priceId"), $hasCache);
        }
    }

    private function createProductWithPrice(string $name, float $price, $id = null): Product
    {
        $product = new Product();
        $product->_id = $id ?? new ObjectId();
        $product->name = $name;
        $this->assertTrue($product->save());
        $this->createPriceFor($product, $price);

        return $product;
    }

    private function createStockFor(Product $product, float $amount): Stock
    {
        $stock = new Stock();
        $stock->product_id = $product->_id;
        $stock->stock = $amount;
        $this->assertTrue($stock->save());

        return $stock;
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
