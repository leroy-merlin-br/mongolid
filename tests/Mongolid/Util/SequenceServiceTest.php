<?php

namespace Mongolid\Util;

use Mockery as m;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use TestCase;

class SequenceServiceTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    /**
     * @dataProvider sequenceScenarios
     */
    public function testShouldGetNextValue($sequenceName, $currentValue, $expectation)
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $sequenceService = m::mock(SequenceService::class.'[rawCollection]', [$connPool]);
        $sequenceService->shouldAllowMockingProtectedMethods();
        $rawCollection = m::mock(Collection::class);

        // Act
        $sequenceService->shouldReceive('rawCollection')
            ->once()
            ->andReturn($rawCollection);

        $rawCollection->shouldReceive('findOneAndUpdate')
            ->once()
            ->with(
                ['_id' => $sequenceName],
                ['$inc' => ['seq' => 1]],
                ['upsert' => true]
            )->andReturn(
                $currentValue ? (object) ['seq' => $currentValue] : null
            );

        // Assertion
        $this->assertEquals(
            $expectation,
            $sequenceService->getNextValue($sequenceName)
        );
    }

    public function testShouldGetRawCollection()
    {
        // Arrange
        $connPool = m::mock(Pool::class);
        $sequenceService = new SequenceService($connPool, 'foobar');
        $connection = m::mock(Connection::class);
        $collection = m::mock(Collection::class);

        $connection->defaultDatabase = 'grimory';
        $connection->grimory = (object) ['foobar' => $collection];

        // Act
        $connPool->shouldReceive('getConnection')
            ->once()
            ->andReturn($connection);

        $connection->shouldReceive('getRawConnection')
            ->andReturn($connection);

        // Assertion
        $this->assertEquals(
            $collection,
            $this->callProtected($sequenceService, 'rawCollection')
        );
    }

    public function sequenceScenarios()
    {
        return [
            'New sequence in collection "products"' => [
                'sequenceName' => 'products',
                'currentValue' => 0,
                'expectation' => 1,
            ],
            // -----------------------
            'Existing sequence in collection "unicorns"' => [
                'sequenceName' => 'unicorns',
                'currentValue' => 7,
                'expectation' => 8,
            ],
            // -----------------------
            'Existing sequence in collection "unicorns"' => [
                'sequenceName' => 'unicorns',
                'currentValue' => 3,
                'expectation' => 4,
            ],
        ];
    }
}
