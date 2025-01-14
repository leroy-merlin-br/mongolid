<?php

namespace Mongolid\Query\EagerLoader;

use Mongolid\Cursor\SchemaEmbeddedCursor;
use Mongolid\TestCase;
use Mockery as m;
use Mongolid\Tests\Stubs\Legacy\Product;
use Mongolid\Util\CacheComponentInterface;

class CacheTest extends TestCase
{
    public function testShouldCacheAllDocuments(): void
    {
        // Set
        $cacheComponent = $this->instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $model = $this->instance(Product::class, m::mock(Product::class));
        $product1 = new Product();
        $product1->_id = 123;
        $product2 = new Product();
        $product2->_id = 456;
        $products = new SchemaEmbeddedCursor(
            Product::class,
            [$product1, $product2]
        );

        $cache = new Cache($cacheComponent);
        $eagerLoadedModels = [
            'key' => '_id',
            'model' => Product::class,
            'ids' => [
                123 => 123,
                456 => 456,
            ],
        ];

        // Expectations
        $model->expects()
            ->where(['_id' => ['$in' => [123, 456]]])
            ->andReturn($products);

        $cacheComponent->expects()
            ->put('products:123', m::any(), 36)
            ->andReturnTrue();

        $cacheComponent->expects()
            ->put('products:456', m::any(), 36)
            ->andReturnTrue();

        // Actions
        $cache->cache($eagerLoadedModels);
    }

    public function testShouldNotCacheIfModelDoesNotHaveIds(): void
    {
        // Set
        $cacheComponent = $this->instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $model = $this->instance(Product::class, m::mock(Product::class));
        $cache = new Cache($cacheComponent);
        $eagerLoadedModels = [
            'key' => '_id',
            'model' => Product::class,
        ];

        // Expectations
        $model->allows('where')
            ->never();

        $cacheComponent->allows('put')
            ->never();

        // Actions
        $cache->cache($eagerLoadedModels);
    }


    public function testShouldCacheOnlyALimitedNumberOfProducts(): void
    {
        // Set
        $cacheComponent = $this->instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $model = $this->instance(Product::class, m::mock(Product::class));
        $products = [];
        for ($i = 1; $i < 200; $i++) {
            $product = new Product();
            $product->_id = $i;
            $products[] = $product;
        }
        $products = new SchemaEmbeddedCursor(Product::class, $products);

        $cache = new Cache($cacheComponent);
        $eagerLoadedModels = [
            'key' => '_id',
            'model' => Product::class,
            'ids' => [
                123 => 123,
                456 => 456,
            ],
        ];

        // Expectations
        $model->expects()
            ->where(['_id' => ['$in' => [123, 456]]])
            ->andReturn($products);

        $cacheComponent->allows('put')
            ->times(100)
            ->andReturnTrue();

        // Actions
        $cache->cache($eagerLoadedModels);
    }
}
