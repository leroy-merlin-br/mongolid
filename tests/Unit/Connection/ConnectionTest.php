<?php
namespace Mongolid\Connection;

use MongoDB\Client;
use Mongolid\TestCase;

class ConnectionTest extends TestCase
{
    public function testShouldConstructANewConnection()
    {
        // Set
        $server = 'mongodb://my-server/my_db';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Actions
        $connection = new Connection($server, $options, $driverOptions);

        // Assertions
        $this->assertAttributeInstanceOf(Client::class, 'client', $connection);
        $this->assertAttributeSame('my_db', 'defaultDatabase', $connection);
    }

    public function testShouldDetermineDatabaseFromACluster()
    {
        // Set
        $server = 'mongodb://my-server,other-server/my_db?replicaSet=someReplica';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Actions
        $connection = new Connection($server, $options, $driverOptions);

        // Assertions
        $this->assertAttributeInstanceOf(Client::class, 'client', $connection);
        $this->assertAttributeSame('my_db', 'defaultDatabase', $connection);
    }

    public function testShouldGetConnection()
    {
        // Set
        $server = 'mongodb://my-server/my_db';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];
        $expectedParameters = [
            'uri' => $server,
            'typeMap' => [
                'array' => 'array',
                'document' => 'array',
            ],
        ];

        // Actions
        $connection = new Connection($server, $options, $driverOptions);
        $client = $connection->getClient();

        // Assertions
        $this->assertAttributeSame($expectedParameters['uri'], 'uri', $client);
        $this->assertAttributeSame($expectedParameters['typeMap'], 'typeMap', $client);
    }
}
