<?php

namespace Mongolid\Tests\Integration;

use DateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Model\ModelInterface;
use Mongolid\Tests\Stubs\Product;
use Mongolid\Tests\Stubs\ProductWithSoftDelete;

final class PersisteModelWithSoftDeleteTest extends IntegrationTestCase
{
    private ObjectId $_id;

    public function testFindNotDeletedProduct(): void
    {
        // Set
        $product = $this->persiteProduct();

        // Actions
        $result = ProductWithSoftDelete::where()->first();

        // Assertion
        $this->assertEquals($product, $result);
    }

    public function testCannotFindDeletedProduct(): void
    {
        // Set
        $this->persiteProduct(true);

        // Actions
        $result = ProductWithSoftDelete::where()->first();

        // Assertion
        $this->assertNull($result);
    }

    public function testFindDeletedProductWithFirst(): void
    {
        // Set
        $product = $this->persiteProduct();

        // Actions
        $result = ProductWithSoftDelete::first($this->_id);

        // Assertion
        $this->assertEquals($product, $result);
    }

    public function testCannotFindDeletedProductWithFirst(): void
    {
        // Set
        $this->persiteProduct(true);

        // Actions
        $result = ProductWithSoftDelete::first($this->_id);

        // Assertion
        $this->assertNull($result);
    }

    public function testRestoreDeletedProduct(): void
    {
        // Set
        $product = $this->persiteProduct(true);

        // Actions
        $isRestored = $product->restore();
        $result = ProductWithSoftDelete::first($this->_id);

        // Assertion
        $this->assertTrue($isRestored);
        $this->assertEquals($product, $result);
    }

    public function testCannotRestoreAlreadyRestoredProduct(): void
    {
        // Set
        $product = $this->persiteProduct(isRestored: true);

        // Actions
        $isRestored = $product->restore();
        $result = ProductWithSoftDelete::first($this->_id);

        // Assertion
        $this->assertFalse($isRestored);
        $this->assertEquals($product, $result);
    }

    public function testExecuteSoftDeleteOnProduct(): void
    {
        // Set
        $product = $this->persiteProduct();

        // Actions
         $isDeleted = $product->delete();
         $result = ProductWithSoftDelete::first($this->_id);

        // Assertion
        $this->assertTrue($isDeleted);
        $this->assertNull($result);
        $this->assertInstanceOf(UTCDateTime::class, $product->deleted_at);
    }

    public function testCannotExecuteSoftDeleteOnProduct(): void
    {
        // Set
        $product = $this->persiteProduct(model:Product::class);

        // Actions
         $isDeleted = $product->delete();
         $result = ProductWithSoftDelete::first($this->_id);

        // Assertion
        $this->assertTrue($isDeleted);
        $this->assertNull($result);
        $this->assertNull($result->deleted_at ?? null);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->_id = new ObjectId('5bcb310783a7fcdf1bf1a672');
    }

    private function persiteProduct(
        bool $softDeleted = false,
        bool $isRestored = false,
        string $model = ProductWithSoftDelete::class
    ): ModelInterface {
        $product = new $model();
        $product->_id = $this->_id;
        $product->short_name = 'Furadeira de Impacto Bosch com Chave de Mandril ';
        $product->name = 'Furadeira de Impacto Bosch com Chave de Mandril e Acessórios 550W 1/2 GSB 550 RE 127V (110V)';

        if ($softDeleted) {
            $date = new UTCDateTime(new DateTime('today'));

            $product->deleted_at = $date;
        }

        if ($isRestored) {
            $product->deleted_at = null;
        }

        $product->save();

        return $product;
    }
}
