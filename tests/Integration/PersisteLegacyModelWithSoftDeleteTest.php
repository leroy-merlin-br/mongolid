<?php

namespace Mongolid\Tests\Integration;

use DateTime;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Model\ModelInterface;
use Mongolid\Tests\Stubs\Legacy\ProductWithSoftDelete;
use Mongolid\Tests\Stubs\Legacy\Product;

final class PersisteLegacyModelWithSoftDeleteTest extends IntegrationTestCase
{
    private ObjectId $_id;

    public function testShouldFindNotDeletedProduct(): void
    {
        // Set
        $product = $this->persiteProduct();

        // Actions
        $actualWhereResult = ProductWithSoftDelete::where()->first();
        $actualFirstResult = ProductWithSoftDelete::first($this->_id);

        // Assertions
        $this->assertEquals($product, $actualWhereResult);
        $this->assertEquals($product, $actualFirstResult);
    }

    public function testCannotFindDeletedProduct(): void
    {
        // Set
        $this->persiteProduct(true);

        // Actions
        $actualWhereResult = ProductWithSoftDelete::where()->first();
        $actualFirstResult = ProductWithSoftDelete::first($this->_id);

        // Assertions
        $this->assertNull($actualWhereResult);
        $this->assertNull($actualFirstResult);
    }

    public function testShouldFindATrashedProduct(): void
    {
        // Set
        $this->persiteProduct(true);
        $this->_id = new ObjectId('5bcb310783a7fcdf1bf1a123');
        $this->persiteProduct();

        // Actions
        $result = ProductWithSoftDelete::withTrashed();
        $resultArray = $result->toArray();

        // Assertions
        $this->assertSame(2, $result->count());
        $this->assertInstanceOf(
            UTCDateTime::class,
            $resultArray[0]['deleted_at']
        );
        $this->assertNull($resultArray[1]['deleted_at'] ?? null);
    }

    public function testRestoreDeletedProduct(): void
    {
        // Set
        $product = $this->persiteProduct();
        $product->delete();

        // Actions
        $isRestored = $product->restore();
        $result = ProductWithSoftDelete::first($this->_id);

        // Assertions
        $this->assertTrue($isRestored);
        $this->assertEquals($product, $result);
    }

    public function testCannotRestoreAlreadyRestoredProduct(): void
    {
        // Set
        $product = $this->persiteProduct();

        // Actions
        $isRestored = $product->restore();
        $result = ProductWithSoftDelete::first($this->_id);

        // Assertions
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
         $deletedProduct = ProductWithSoftDelete::withTrashed()->first();

        // Assertions
        $this->assertTrue($isDeleted);
        $this->assertNull($result);
        $this->assertEquals($product, $deletedProduct);
        $this->assertInstanceOf(
            UTCDateTime::class,
            $deletedProduct->deleted_at
        );
    }

    public function testExecuteForceDeleteOnProduct(): void
    {
        // Set
        $product = $this->persiteProduct();
        $this->_id = new ObjectId('5bcb310783a7fcdf1bf1a123');
        $product2 = $this->persiteProduct();

        // Actions
         $isDeleted = $product->forceDelete();
         $result = ProductWithSoftDelete::withTrashed();

        // Assertions
        $this->assertTrue($isDeleted);
        $this->assertSame(1, $result->count());
        $this->assertEquals($product2, $result->first());
    }

    public function testCannotExecuteSoftDeleteOnProduct(): void
    {
        // Set
        $product = $this->persiteProduct(model:Product::class);

        // Actions
         $isDeleted = $product->delete();
         $result = ProductWithSoftDelete::first($this->_id);

        // Assertions
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

        $product->save();

        return $product;
    }
}
