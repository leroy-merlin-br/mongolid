<?php

namespace Mongolid\Connection;

use Illuminate\Container\Container;
use Mockery as m;
use MongoDB\Client;
use MongoDB\Driver\Manager;
use Mongolid\Container\Ioc;
use TestCase;

class ConnectionTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldConstructANewConnection()
    {
        // Arrange
        $server = 'mongodb://my-server/my_db';
        $options = ['connect' => true];
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
        $options = ['connect' => true];
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
        $options = ['connect' => true];
        $driverOptions = ['some', 'driver', 'options'];

        // Act
        $connection = new Connection($server, $options, $driverOptions);
        $rawManager = $connection->getRawManager();

        // Assert
        $this->assertInstanceOf(Manager::class, $rawManager);
    }
}
