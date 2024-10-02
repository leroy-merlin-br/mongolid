<?php

namespace Mongolid\Query\EagerLoader;

use ArrayIterator;
use MongoDB\BSON\ObjectId;
use Mongolid\Container\Container;
use Mongolid\Cursor\SchemaEmbeddedCursor;
use Mongolid\TestCase;
use Mockery as m;
use Mongolid\Tests\Stubs\Price;
use Mongolid\Tests\Stubs\Legacy\Product;
use Mongolid\Util\CacheComponentInterface;

class EagerLoaderTest extends TestCase
{
    public function testShouldCacheQueries(): void
    {
        // Set
        $cacheComponent = Container::instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $price = Container::instance(Price::class, m::mock(Price::class));
        $eagerLoader = new EagerLoader();
        $product = new Product();
        $product->_id = 1234;

        $priceModel = new Price();
        $priceModel->_id = 1234;
        $prices = new SchemaEmbeddedCursor(Price::class, [$priceModel]);

        $products = new ArrayIterator([$product->toArray()]);

        // Expectations
        $price->expects()
            ->where(['_id' => ['$in' => [1234]]])
            ->andReturn($prices);

        $cacheComponent->expects()
            ->put('prices:1234', m::any(), 36)
            ->andReturnTrue();

        // Actions
        $eagerLoader->cache($products, [
            'price' => [
                'key' => '_id',
                'model' => Price::class,
            ],
        ]);
    }

    public function testShouldCacheQueriesWithObjectIds(): void
    {
        // Set
        $cacheComponent = Container::instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $price = Container::instance(Price::class, m::mock(Price::class));
        $eagerLoader = new EagerLoader();
        $product = new Product();
        $id = new ObjectId();
        $product->_id = $id;

        $priceModel = new Price();
        $priceModel->_id = $id;
        $prices = new SchemaEmbeddedCursor(Price::class, [$priceModel]);

        $products = new ArrayIterator([$product->toArray()]);

        // Expectations
        $price->expects()
            ->where(['_id' => ['$in' => [$id]]])
            ->andReturn($prices);

        $cacheComponent->expects()
            ->put("prices:$id", m::any(), 36)
            ->andReturnTrue();

        // Actions
        $eagerLoader->cache($products, [
            'price' => [
                'key' => '_id',
                'model' => Price::class,
            ],
        ]);
    }

    public function testShouldNotCacheIfEagerLoadingIsEmpty(): void
    {
        // Set
        $cacheComponent = Container::instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $price = Container::instance(Price::class, m::mock(Price::class));
        $eagerLoader = new EagerLoader();
        $product = new Product();
        $product->_id = 1234;

        $products = new ArrayIterator([$product->toArray()]);

        // Expectations
        $price->expects()
            ->where(['_id' => ['$in' => [1234]]])
            ->never();

        $cacheComponent->expects()
            ->put('prices:1234', m::any(), 36)
            ->never();

        // Actions
        $eagerLoader->cache($products, []);
    }

    public function testShouldNotCacheIfCursorIsEmpty(): void
    {
        // Set
        $cacheComponent = Container::instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $price = Container::instance(Price::class, m::mock(Price::class));
        $eagerLoader = new EagerLoader();
        $products = new ArrayIterator([]);

        // Expectations
        $price->expects()
            ->where(['_id' => ['$in' => [1234]]])
            ->never();

        $cacheComponent->expects()
            ->put('prices:1234', m::any(), 36)
            ->never();

        // Actions
        $eagerLoader->cache($products, [
            'price' => [
                'key' => '_id',
                'model' => Price::class,
            ],
        ]);
    }

    public function testShouldNotCacheIfItDidNotFindAnyIdsOnModels(): void
    {
        // Set
        $cacheComponent = Container::instance(
            CacheComponentInterface::class,
            m::mock(CacheComponentInterface::class)
        );
        $price = Container::instance(Price::class, m::mock(Price::class));
        $eagerLoader = new EagerLoader();
        $product = new Product();
        $product->_id = 1234;

        $prices = new SchemaEmbeddedCursor(Price::class, []);

        $products = new ArrayIterator([$product->toArray()]);

        // Expectations
        $price->expects()
            ->where(['_id' => ['$in' => [1234]]])
            ->andReturn($prices);

        $cacheComponent->expects()
            ->put('prices:1234', m::any(), 36)
            ->never();

        // Actions
        $eagerLoader->cache($products, [
            'price' => [
                'key' => '_id',
                'model' => Price::class,
            ],
        ]);
    }
}
