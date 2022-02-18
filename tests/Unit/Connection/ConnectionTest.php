<?php

namespace Mongolid\Connection;

use MongoDB\Client;
use MongoDB\Driver\Manager;
use Mongolid\TestCase;

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
        $this->assertInstanceOf(Client::class, $connection->getRawConnection());
        $this->assertEquals('my_db', $connection->defaultDatabase);
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
        $this->assertInstanceOf(Client::class, $connection->getRawConnection());
        $this->assertEquals('my_db', $connection->defaultDatabase);
    }

    public function testShouldGetRawConnection(): void
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
        $this->assertEquals($expectedParameters['uri'], $rawConnection);
        $this->assertEquals($expectedParameters['typeMap'], $rawConnection->getTypeMap());
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
