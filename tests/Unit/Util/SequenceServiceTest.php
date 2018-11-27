<?php
namespace Mongolid\Util;

use Mockery as m;
use MongoDB\Collection;
use Mongolid\Connection\Connection;
use Mongolid\TestCase;

class SequenceServiceTest extends TestCase
{
    /**
     * @dataProvider sequenceScenarios
     */
    public function testShouldGetNextValue($sequenceName, $currentValue, $expectation)
    {
        // Set
        $connection = m::mock(Connection::class);
        $sequenceService = m::mock(SequenceService::class.'[rawCollection]', [$connection]);
        $sequenceService->shouldAllowMockingProtectedMethods();
        $rawCollection = m::mock(Collection::class);

        // Expectations
        $sequenceService->expects()
            ->rawCollection()
            ->andReturn($rawCollection);

        $rawCollection->expects()
            ->findOneAndUpdate(
                ['_id' => $sequenceName],
                ['$inc' => ['seq' => 1]],
                ['upsert' => true]
            )->andReturn(
                $currentValue ? (object) ['seq' => $currentValue] : null
            );

        // Actions
        $result = $sequenceService->getNextValue($sequenceName);

        // Assertions
        $this->assertSame($expectation, $result);
    }

    public function testShouldGetRawCollection()
    {
        // Set
        $connection = m::mock(Connection::class);
        $sequenceService = new SequenceService($connection, 'foobar');
        $collection = m::mock(Collection::class);

        $connection->defaultDatabase = 'grimory';
        $connection->grimory = (object) ['foobar' => $collection];

        // Expectations
        $connection->expects()
            ->getRawConnection()
            ->andReturnSelf($connection);

        // Actions
        $result = $this->callProtected($sequenceService, 'rawCollection');

        // Assertions
        $this->assertSame($collection, $result);
    }

    public function sequenceScenarios(): array
    {
        return [
            'New sequence in collection "products"' => [
                'sequenceName' => 'products',
                'currentValue' => 0,
                'expectation' => 1,
            ],
            'Existing sequence in collection "unicorns"' => [
                'sequenceName' => 'unicorns',
                'currentValue' => 7,
                'expectation' => 8,
            ],
        ];
    }
}
