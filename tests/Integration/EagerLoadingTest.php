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
