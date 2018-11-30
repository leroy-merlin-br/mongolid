<?php
namespace Mongolid\Util;

use Mockery as m;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use Mongolid\Connection\Connection;
use Mongolid\TestCase;

class SequenceServiceTest extends TestCase
{
    /**
     * @dataProvider sequenceScenarios
     */
    public function testShouldGetNextValue(string $sequenceName, int $currentValue, int $expectation)
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

    public function testShouldGetClient()
    {
        // Set
        $connection = m::mock(Connection::class);
        $connection->defaultDatabase = 'production';

        $sequenceService = new SequenceService($connection, 'foobar');
        $collection = m::mock(Collection::class);

        $client = m::mock(Client::class);
        $database = m::mock(Database::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        $client->expects()
            ->selectDatabase('production')
            ->andReturn($database);

        $database->expects()
            ->selectCollection('foobar')
            ->andReturn($collection);

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
