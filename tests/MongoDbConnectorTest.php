<?php

use Mockery as m;

class MongoDbConnectorTest extends PHPUnit_Framework_TestCase
{
    public function testShouldCreateAndRetrieveANewConnection()
    {
        // Set
        $conector            = m::mock('Zizaco\Mongolid\MongoDbConnector[newMongoClient]');
        $expectedMongoClient = m::mock('MongoClient');
        $conector->shouldAllowMockingProtectedMethods();

        // Expectation
        $conector->shouldReceive('newMongoClient')
            ->with('127.0.0.1:27019')
            ->once()
            ->andReturn($expectedMongoClient);

        // Acceptance
        $result = $conector->getConnection('127.0.0.1:27019');
        $this->assertEquals($expectedMongoClient, $result);
        $this->assertAttributeEquals('127.0.0.1:27019', 'defaultConnectionString', $conector);
        $this->assertEquals(
            ['127.0.0.1:27019' => $expectedMongoClient],
            $conector->connections
        );
    }

    public function testShouldCreateAndHandleMultipleConnections()
    {
        // Set
        $conector          = m::mock('Zizaco\Mongolid\MongoDbConnector[newMongoClient]');
        $localMongoClient  = m::mock('MongoClient');
        $remoteMongoClient = m::mock('MongoClient');
        $conector->shouldAllowMockingProtectedMethods();

        // Expectation
        $conector->shouldReceive('newMongoClient')
            ->with('127.0.0.1:27019')
            ->once()
            ->andReturn($localMongoClient);

        $conector->shouldReceive('newMongoClient')
            ->with('189.12.12.19:27019')
            ->once()
            ->andReturn($remoteMongoClient);

        // Acceptance
        $localResult  = $conector->getConnection('127.0.0.1:27019');
        $remoteResult = $conector->getConnection('189.12.12.19:27019');
        $this->assertEquals($localMongoClient, $localResult);
        $this->assertEquals($remoteMongoClient, $remoteResult);
        $this->assertAttributeEquals('127.0.0.1:27019', 'defaultConnectionString', $conector);
        $this->assertEquals(
            [
                '127.0.0.1:27019' => $localMongoClient,
                '189.12.12.19:27019' => $remoteMongoClient
            ],
            $conector->connections
        );
    }

    public function testShouldBeAbleToSetConnectionStringToConnectLater()
    {
        // Set
        $conector            = m::mock('Zizaco\Mongolid\MongoDbConnector[newMongoClient]');
        $expectedMongoClient = m::mock('MongoClient');
        $conector->shouldAllowMockingProtectedMethods();

        // Expectation
        $conector->shouldReceive('newMongoClient')
            ->with('127.0.0.1:27019')
            ->once()
            ->andReturn($expectedMongoClient);

        // Acceptance
        $conector->defaultConnectionString = '127.0.0.1:27019';
        $this->assertEmpty($conector->connections);
        $this->assertEquals($expectedMongoClient, $conector->getConnection());
    }
}
