<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Stubs\Price;
use Mongolid\Tests\Stubs\Product;
use Mongolid\Tests\Stubs\ReferencedUser;

final class EagerLoadingTest extends IntegrationTestCase
{
    public function testShouldCreateAPriceAssociatedWithProduct(): void
    {
        $this->createProductWithPrice('Playstation', 299.90);
        $this->createProductWithPrice('Xbox', 199.90);
        $this->createProductWithPrice('Switch', 399.90);

        $cursor = Product::all();
        $product = $cursor->first();

        $this->assertInstanceOf(Price::class, $product->price);
        $this->assertSame($product->price->value, 299.90);
    }

    public function testShouldEagerLoadingAllPrices(): void
    {
        $this->createProductWithPrice('Playstation', 299.90);
        $this->createProductWithPrice('Xbox', 199.90);
        $this->createProductWithPrice('Switch', 399.90);

        $cursor = Product::all();
        $product = $cursor->first();

        $this->assertInstanceOf(Price::class, $product->price);
        $this->assertSame($product->price->value, 299.90);
    }

    private function createProductWithPrice(string $name, float $price): Product
    {
        // Product
        $product = new Product();
        $product->name = $name;

        // Price
        $priceModel = new Price();
        $priceModel->value = $price;
        $this->assertTrue($priceModel->save());

        $product->price()->attach($priceModel);
        $this->assertTrue($product->save());

        return $product;
    }
}
