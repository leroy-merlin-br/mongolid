<?php

namespace Mongolid\Cursor;

use ArrayIterator;
use ErrorException;
use IteratorIterator;
use Mockery as m;
use MongoDB\Collection;
use Mongolid\Container\Container;
use Mongolid\Util\CacheComponentInterface;
use Mongolid\TestCase;

class CacheableCursorTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldGetCursorFromPreviousIteration()
    {
        // Arrange
        $documentsFromDb = new ArrayIterator([['name' => 'joe'], ['name' => 'doe']]);
        $cursor = $this->getCacheableCursor();
        $this->setProtected(
            $cursor,
            'documents',
            $documentsFromDb
        );

        // Assert
        $this->assertEquals(
            new ArrayIterator($documentsFromDb),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGetCursorFromCache()
    {
        // Arrange
        $documentsFromCache = [['name' => 'joe'], ['name' => 'doe']];
        $cursor = $this->getCacheableCursor();
        $cacheComponent = m::mock(CacheComponentInterface::class);

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->andReturn('find:collection:123');

        Container::instance(CacheComponentInterface::class, $cacheComponent);

        $cacheComponent->shouldReceive('get')
            ->once()
            ->with('find:collection:123', null)
            ->andReturn($documentsFromCache);

        // Assert
        $this->assertEquals(
            new ArrayIterator($documentsFromCache),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGetFromDatabaseWhenCacheFails()
    {
        // Arrange
        $documentsFromDb = [['name' => 'joe'], ['name' => 'doe']];
        $cursor = $this->getCacheableCursor()->limit(150);
        $cacheComponent = m::mock(CacheComponentInterface::class);
        $rawCollection = m::mock();
        $cacheKey = 'find:collection:123';

        $this->setProtected(
            $cursor,
            'collection',
            $rawCollection
        );

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->andReturn($cacheKey);

        Container::instance(CacheComponentInterface::class, $cacheComponent);

        $cacheComponent->shouldReceive('get')
            ->with($cacheKey, null)
            ->andThrow(
                new ErrorException(
                    sprintf('Unable to unserialize cache %s', $cacheKey)
                )
            );

        $rawCollection->shouldReceive('find')
            ->with([], ['limit' => 100])
            ->andReturn(new ArrayIterator($documentsFromDb));

        $cacheComponent->shouldReceive('put')
            ->once()
            ->with($cacheKey, $documentsFromDb, m::any());

        // Assert
        $this->assertEquals(
            new ArrayIterator($documentsFromDb),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGetCursorFromDatabaseAndCacheForLater()
    {
        // Arrange
        $documentsFromDb = [['name' => 'joe'], ['name' => 'doe']];
        $cursor = $this->getCacheableCursor()->limit(150);
        $cacheComponent = m::mock(CacheComponentInterface::class);
        $rawCollection = m::mock();

        $this->setProtected(
            $cursor,
            'collection',
            $rawCollection
        );

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->andReturn('find:collection:123');

        Container::instance(CacheComponentInterface::class, $cacheComponent);

        $cacheComponent->shouldReceive('get')
            ->with('find:collection:123', null)
            ->andReturn(null);

        $rawCollection->shouldReceive('find')
            ->with([], ['limit' => 100])
            ->andReturn(new ArrayIterator($documentsFromDb));

        $cacheComponent->shouldReceive('put')
            ->once()
            ->with('find:collection:123', $documentsFromDb, m::any());

        // Assert
        $this->assertEquals(
            new ArrayIterator($documentsFromDb),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGetOriginalCursorFromDatabaseAfterTheDocumentLimit()
    {
        // Arrange
        $documentsFromDb = [['name' => 'joe'], ['name' => 'doe']];
        $cursor = $this->getCacheableCursor()->limit(150);
        $cacheComponent = m::mock(CacheComponentInterface::class);
        $rawCollection = m::mock();

        $this->setProtected(
            $cursor,
            'position',
            CacheableCursor::DOCUMENT_LIMIT + 1
        );

        $this->setProtected(
            $cursor,
            'collection',
            $rawCollection
        );

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->never();

        Container::instance(CacheComponentInterface::class, $cacheComponent);

        $cacheComponent->shouldReceive('get')
            ->with('find:collection:123', null)
            ->never();

        $rawCollection->shouldReceive('find')
            ->with([], ['skip' => CacheableCursor::DOCUMENT_LIMIT, 'limit' => 49])
            ->andReturn(new ArrayIterator($documentsFromDb));

        $cacheComponent->shouldReceive('put')
            ->never();

        // Assert
        $this->assertEquals(
            (new ArrayIterator($documentsFromDb))->current(),
            $this->callProtected($cursor, 'getCursor')->current()
        );
    }

    public function testShouldGenerateUniqueCacheKey()
    {
        // Arrange
        $cursor = $this->getCacheableCursor(null, 'find', [['color' => 'red']]);

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->passthru();

        $expectedCacheKey = sprintf(
            '%s:%s:%s',
            'find',
            'my_db.my_collection',
            md5(serialize([['color' => 'red']]))
        );

        // Assert

        $this->assertEquals(
            $expectedCacheKey,
            $cursor->generateCacheKey()
        );
    }

    protected function getCacheableCursor(
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ) {
        if (!$collection) {
            $collection = m::mock(Collection::class);
            $collection->shouldReceive('getNamespace')
                ->andReturn('my_db.my_collection');
            $collection->shouldReceive('getCollectionName')
                ->andReturn('my_collection');
        }

        $mock = m::mock(
            CacheableCursor::class . '[generateCacheKey]',
            [$collection, $command, $params]
        );
        $mock->shouldAllowMockingProtectedMethods();

        if ($driverCursor) {
            $mock->shouldReceive('getCursor')
                ->andReturn($driverCursor);
        }

        return $mock;
    }
}

