<?php

namespace Mongolid\Cursor;

use ArrayObject;
use Mockery as m;
use MongoDB\Collection;
use Mongolid\Container\Ioc;
use Mongolid\Schema;
use Mongolid\Util\CacheComponent;
use TestCase;

class CacheableCursorTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldGetCursorFromPreviousIteration()
    {
        // Arrange
        $documentsFromDb = [['name' => 'joe'], ['name' => 'doe']];
        $cursor          = $this->getCachableCursor();
        $this->setProtected(
            $cursor,
            'documents',
            $documentsFromDb
        );

        // Assert
        $this->assertEquals(
            new ArrayObject($documentsFromDb),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGetCursorFromCache()
    {
        // Arrange
        $documentsFromCache = [['name' => 'joe'], ['name' => 'doe']];
        $cursor             = $this->getCachableCursor();
        $cacheComponent = m::mock(CacheComponent::class);

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->andReturn('find:collection:123');

        Ioc::instance(CacheComponent::class, $cacheComponent);

        $cacheComponent->shouldReceive('get')
            ->once()
            ->with('find:collection:123', null)
            ->andReturn($documentsFromCache);

        // Assert
        $this->assertEquals(
            new ArrayObject($documentsFromCache),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGetCursorFromDatabaseAndCacheForLater()
    {
        // Arrange
        $documentsFromDb = [['name' => 'joe'], ['name' => 'doe']];
        $cursor             = $this->getCachableCursor();
        $cacheComponent = m::mock(CacheComponent::class);
        $rawCollection  = m::mock();

        $this->setProtected(
            $cursor,
            'collection',
            $rawCollection
        );

        // Act
        $cursor->shouldReceive('generateCacheKey')
            ->andReturn('find:collection:123');

        Ioc::instance(CacheComponent::class, $cacheComponent);

        $cacheComponent->shouldReceive('get')
            ->with('find:collection:123', null)
            ->andReturn(null);

        $rawCollection->shouldReceive('find')
            ->andReturn(new ArrayObject($documentsFromDb));

        $cacheComponent->shouldReceive('put')
            ->once()
            ->with('find:collection:123', $documentsFromDb, m::any());

        // Assert
        $this->assertEquals(
            new ArrayObject($documentsFromDb),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    protected function getCachableCursor(
        $entitySchema = null,
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ) {
        if (! $entitySchema) {
            $entitySchema = m::mock(Schema::class . '[]');
        }

        if (! $collection) {
            $collection = m::mock(Collection::class);
        }

        $mock = m::mock(
            CacheableCursor::class . '[generateCacheKey]',
            [$entitySchema, $collection, $command, $params]
        );
        $mock->shouldAllowMockingProtectedMethods();

        if ($driverCursor) {
            $mock->shouldReceive('getCursor')
                ->andReturn($driverCursor);
        }

        return $mock;
    }
}
