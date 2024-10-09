<?php

namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Model\ModelInterface;
use Mongolid\Tests\Stubs\Legacy\ProductWithSoftDelete;
use Mongolid\Tests\Stubs\Legacy\Product;

final class PersistLegacyModelWithSoftDeleteTest extends IntegrationTestCase
{
    public function testShouldFindUndeletedProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();

        // Actions
        $actualWhereResult = ProductWithSoftDelete::where()->first();
        $actualFirstResult = ProductWithSoftDelete::first(
            '5bcb310783a7fcdf1bf1a672'
        );

        // Assertions
        $this->assertEquals($product, $actualWhereResult);
        $this->assertEquals($product, $actualFirstResult);
    }

    public function testShouldNotFindDeletedProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();
        $product->delete();

        // Actions
        $actualWhereResult = ProductWithSoftDelete::where()->first();
        $actualFirstResult = ProductWithSoftDelete::first(
            '5bcb310783a7fcdf1bf1a672'
        );

        // Assertions
        $this->assertNull($actualWhereResult);
        $this->assertNull($actualFirstResult);
    }

    public function testShouldFindATrashedProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();
        $product->delete();
        $this->persistProductWithSoftDeleteTrait('5bcb310783a7fcdf1bf1a123');

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

    public function testShouldRestoreDeletedProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();
        $product->delete();

        // Actions
        $isRestored = $product->restore();
        $result = ProductWithSoftDelete::first('5bcb310783a7fcdf1bf1a672');

        // Assertions
        $this->assertTrue($isRestored);
        $this->assertEquals($product, $result);
    }

    public function testShouldNotRestoreAlreadyRestoredProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();

        // Actions
        $isRestored = $product->restore();
        $result = ProductWithSoftDelete::first('5bcb310783a7fcdf1bf1a672');

        // Assertions
        $this->assertFalse($isRestored);
        $this->assertEquals($product, $result);
    }

    public function testShouldExecuteSoftDeleteOnProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();

        // Actions
         $isDeleted = $product->delete();
         $result = ProductWithSoftDelete::first('5bcb310783a7fcdf1bf1a672');
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

    public function testShouldExecuteForceDeleteOnProduct(): void
    {
        // Set
        $product = $this->persistProductWithSoftDeleteTrait();
        $product2 = $this->persistProductWithSoftDeleteTrait(
            '5bcb310783a7fcdf1bf1a123'
        );

        // Actions
         $isDeleted = $product->forceDelete();
         $result = ProductWithSoftDelete::withTrashed();

        // Assertions
        $this->assertTrue($isDeleted);
        $this->assertSame(1, $result->count());
        $this->assertEquals($product2, $result->first());
    }

    public function testShouldNotExecuteSoftDeleteOnProduct(): void
    {
        // Set
        $product = $this->persistProduct();

        // Actions
         $isDeleted = $product->delete();
         $result = ProductWithSoftDelete::first('5bcb310783a7fcdf1bf1a672');

        // Assertions
        $this->assertTrue($isDeleted);
        $this->assertNull($result);
        $this->assertNull($result->deleted_at ?? null);
    }

    private function persistProductWithSoftDeleteTrait(
        string $id = '5bcb310783a7fcdf1bf1a672'
    ): ModelInterface {
        $product = new ProductWithSoftDelete();
        $product->_id = new ObjectId($id);
        $product->short_name = 'Furadeira de Impacto Bosch com Chave de Mandril ';
        $product->name = 'Furadeira de Impacto Bosch com Chave de Mandril e Acessórios 550W 1/2 GSB 550 RE 127V (110V)';

        $product->save();

        return $product;
    }

    private function persistProduct(
        string $id = '5bcb310783a7fcdf1bf1a672'
    ): ModelInterface {
        $product = new Product();
        $product->_id = new ObjectId($id);
        $product->short_name = 'Furadeira de Impacto Bosch com Chave de Mandril ';
        $product->name = 'Furadeira de Impacto Bosch com Chave de Mandril e Acessórios 550W 1/2 GSB 550 RE 127V (110V)';

        $product->save();

        return $product;
    }
}
