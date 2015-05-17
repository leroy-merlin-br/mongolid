<?php
namespace Mongolid\Connection;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

class ConnectionTest extends TestCase
{

    public function tearDown()
    {
        Connection::$sharedConnection = null;

        parent::tearDown();
        m::close();
    }

    public function testShouldCreateANewConnection()
    {
        // Arrange
        $mongoClientMock = m::mock('MongoClient');
        $connector       = Ioc::make('Mongolid\Connection\Connection');

        Ioc::instance('MongoClient', $mongoClientMock);

        // Act
        $this->callProtected($connector, 'createConnection');

        // Assert
        $this->assertEquals($mongoClientMock, $connector::$sharedConnection);
    }

    public function testShouldNotCreateAConnectionWithAlreadyCreatedOne()
    {
        // Arrange
        $connector                    = Ioc::make('Mongolid\Connection\Connection');
        $mock                         = m::mock('MongoClient');
        $connector::$sharedConnection = $mock;

        // Act
        $this->callProtected($connector, 'createConnection');

        // Assert
        $this->assertEquals($mock, $connector::$sharedConnection);
    }

    /**
     * @expectedException MongoConnectionException
     */
    public function testShouldRaiseExceptionForAConnectionStringInvalid()
    {
        // Arrange
        $connector = Ioc::make('Mongolid\Connection\Connection');

        // Act
        $this->callProtected($connector, 'createConnection');
    }

    public function testShouldReturnConnectionInstance()
    {
        // Arrange
        $connector = Ioc::make('Mongolid\Connection\Connection');
        $mock                         = m::mock('MongoClient');
        $connector::$sharedConnection = $mock;

        $instance = $connector->getConnectionInstance();

        $this->assertEquals($instance, $mock);
    }
}
