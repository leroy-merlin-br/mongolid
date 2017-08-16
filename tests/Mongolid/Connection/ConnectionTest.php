<?php

namespace Mongolid\Connection;

use MongoDB\Client;
use MongoDB\Driver\Manager;
use TestCase;

class ConnectionTest extends TestCase
{
    public function testShouldConstructANewConnection()
    {
        // Arrange
        $server = 'mongodb://my-server/my_db';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Act
        $connection = new Connection($server, $options, $driverOptions);

        // Assert
        $this->assertAttributeInstanceOf(Client::class, 'rawConnection', $connection);
        $this->assertAttributeEquals('my_db', 'defaultDatabase', $connection);
    }

    public function testShouldDetermineDatabaseFromACluster()
    {
        // Arrange
        $server = 'mongodb://my-server,other-server/my_db?replicaSet=someReplica';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Act
        $connection = new Connection($server, $options, $driverOptions);

        // Assert
        $this->assertAttributeInstanceOf(Client::class, 'rawConnection', $connection);
        $this->assertAttributeEquals('my_db', 'defaultDatabase', $connection);
    }

    public function testShouldGetRawConnection()
    {
        // Arrange
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

        // Act
        $connection = new Connection($server, $options, $driverOptions);
        $rawConnection = $connection->getRawConnection();

        // Assert
        $this->assertAttributeEquals($expectedParameters['uri'], 'uri', $rawConnection);
        $this->assertAttributeEquals($expectedParameters['typeMap'], 'typeMap', $rawConnection);
    }

    public function testShouldGetRawManager()
    {
        // Arrange
        $server = 'mongodb://my-server/my_db';
        $options = ['some', 'uri', 'options'];
        $driverOptions = ['some', 'driver', 'options'];

        // Act
        $connection = new Connection($server, $options, $driverOptions);
        $rawManager = $connection->getRawManager();

        // Assert
        $this->assertInstanceOf(Manager::class, $rawManager);
    }
}
