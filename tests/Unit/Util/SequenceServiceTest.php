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
        // Arrange
        $connection = m::mock(Connection::class);
        $sequenceService = m::mock(SequenceService::class.'[rawCollection]', [$connection]);
        $sequenceService->shouldAllowMockingProtectedMethods();
        $rawCollection = m::mock(Collection::class);

        // Act
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

        // Assertion
        $this->assertEquals(
            $expectation,
            $sequenceService->getNextValue($sequenceName)
        );
    }

    public function testShouldGetRawCollection()
    {
        // Arrange
        $connection = m::mock(Connection::class);
        $sequenceService = new SequenceService($connection, 'foobar');
        $collection = m::mock(Collection::class);

        $connection->defaultDatabase = 'grimory';
        $connection->grimory = (object) ['foobar' => $collection];

        // Act
        $connection->expects()
            ->getRawConnection()
            ->andReturnSelf($connection);

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
