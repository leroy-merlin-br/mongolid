<?php
namespace Mongolid\Connection;

use MongoDB\Client;
use Mongolid\TestCase;

class ConnectionTest extends TestCase
{
    public function testShouldConstructANewConnection(): void
    {
        // Set
        $server = 'mongodb://my-server/my_db';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Actions
        $connection = new Connection($server, $options, $driverOptions);
        $result = $this->getProtected($connection, 'client');

        // Assertions
        $this->assertInstanceOf(Client::class, $result);
        $this->assertSame('my_db', $connection->defaultDatabase);
    }

    public function testShouldDetermineDatabaseFromACluster(): void
    {
        // Set
        $server = 'mongodb://my-server,other-server/my_db?replicaSet=someReplica';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Actions
        $connection = new Connection($server, $options, $driverOptions);
        $result = $this->getProtected($connection, 'client');

        // Assertions
        $this->assertInstanceOf(Client::class, $result);
        $this->assertSame('my_db', $connection->defaultDatabase);
    }

    public function testShouldGetConnection(): void
    {
        // Set
        $server = 'mongodb://my-server/my_db';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];
        $expectedParameters = [
            'uri' => $server,
            'typeMap' => ['array' => 'array'],
        ];

        // Actions
        $connection = new Connection($server, $options, $driverOptions);
        $client = $connection->getClient();

        // Assertions
        $this->assertSame($expectedParameters['uri'], (string) $client);
        $this->assertSame($expectedParameters['typeMap'], $client->getTypeMap());
    }
}
