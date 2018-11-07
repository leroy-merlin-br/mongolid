<?php
namespace Mongolid\Cursor;

use ArrayIterator;
use ErrorException;
use IteratorIterator;
use Mockery as m;
use MongoDB\Collection;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;
use Mongolid\Util\CacheComponentInterface;

class CacheableCursorTest extends TestCase
{
    public function testShouldGetCursorFromPreviousIteration()
    {
        // Arrange
        $documentsFromDb = new ArrayIterator([['name' => 'joe'], ['name' => 'doe']]);
        $cursor = $this->getCachableCursor();
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
        $cursor = $this->getCachableCursor();
        $cacheComponent = $this->instance(CacheComponentInterface::class, m::mock(CacheComponentInterface::class));

        // Act
        $cursor->expects()
            ->generateCacheKey()
            ->andReturn('find:collection:123');

        $cacheComponent->expects()
            ->get('find:collection:123', null)
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
        $cursor = $this->getCachableCursor()->limit(150);
        $cacheComponent = $this->instance(CacheComponentInterface::class, m::mock(CacheComponentInterface::class));
        $rawCollection = m::mock();
        $cacheKey = 'find:collection:123';

        $this->setProtected(
            $cursor,
            'collection',
            $rawCollection
        );

        // Act
        $cursor->expects()
            ->generateCacheKey()
            ->andReturn($cacheKey);

        $cacheComponent->expects()
            ->get($cacheKey, null)
            ->andThrow(
                new ErrorException(
                    sprintf('Unable to unserialize cache %s', $cacheKey)
                )
            );

        $rawCollection->expects()
            ->find([], ['limit' => 100])
            ->andReturn(new ArrayIterator($documentsFromDb));

        $cacheComponent->expects()
            ->put($cacheKey, $documentsFromDb, m::any());

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
        $cursor = $this->getCachableCursor()->limit(150);
        $cacheComponent = $this->instance(CacheComponentInterface::class, m::mock(CacheComponentInterface::class));
        $rawCollection = m::mock();

        $this->setProtected(
            $cursor,
            'collection',
            $rawCollection
        );

        // Act
        $cursor->expects()
            ->generateCacheKey()
            ->andReturn('find:collection:123');

        $cacheComponent->expects()
            ->get('find:collection:123', null)
            ->andReturn(null);

        $rawCollection->expects()
            ->find([], ['limit' => 100])
            ->andReturn(new ArrayIterator($documentsFromDb));

        $cacheComponent->expects()
            ->put('find:collection:123', $documentsFromDb, m::any());

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
        $cursor = $this->getCachableCursor()->limit(150);
        $cacheComponent = $this->instance(CacheComponentInterface::class, m::mock(CacheComponentInterface::class));
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
        $cursor->expects()
            ->generateCacheKey()
            ->never();

        $cacheComponent->expects()
            ->get('find:collection:123', null)
            ->never();

        $rawCollection->expects()
            ->find([], ['skip' => CacheableCursor::DOCUMENT_LIMIT, 'limit' => 49])
            ->andReturn(new ArrayIterator($documentsFromDb));

        $cacheComponent->expects()
            ->put()
            ->never();

        // Assert
        $this->assertEquals(
            new IteratorIterator(new ArrayIterator($documentsFromDb)),
            $this->callProtected($cursor, 'getCursor')
        );
    }

    public function testShouldGenerateUniqueCacheKey()
    {
        // Arrange
        $cursor = $this->getCachableCursor(null, null, 'find', [['color' => 'red']]);

        // Act
        $cursor->expects()
            ->generateCacheKey()
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

    protected function getCachableCursor(
        $entitySchema = null,
        $collection = null,
        $command = 'find',
        $params = [[]],
        $driverCursor = null
    ) {
        if (!$entitySchema) {
            $entitySchema = m::mock(Schema::class.'[]');
        }

        if (!$collection) {
            $collection = m::mock(Collection::class);

            $collection->allows()
                ->getNamespace()
                ->andReturn('my_db.my_collection');

            $collection->allows()
                ->getCollectionName()
                ->andReturn('my_collection');
        }

        $mock = m::mock(
            CacheableCursor::class.'[generateCacheKey]',
            [$entitySchema, $collection, $command, $params]
        );
        $mock->shouldAllowMockingProtectedMethods();

        if ($driverCursor) {
            $mock->expects()
                ->getCursor()
                ->andReturn($driverCursor);
        }

        return $mock;
    }
}
