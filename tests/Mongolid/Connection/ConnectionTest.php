<?php namespace Mongolid\Mongolid\Connection;

use TestCase;
use Mockery as m;
use IOC;

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
        $connector       = IOC::make('Mongolid\Mongolid\Connection\Connection');

        IOC::instance('MongoClient', $mongoClientMock);

        // Act
        $connector->createConnection();

        // Assert
        $this->assertEquals($mongoClientMock, $connector::$sharedConnection);
    }

    public function testShouldNotCreateAConnectionWithAlreadyCreatedOne()
    {
        // Arrange
        $connector = IOC::make('Mongolid\Mongolid\Connection\Connection');
        $mock      = m::mock('MongoClient');
        $connector::$sharedConnection = $mock;

        // Act
        $connector->createConnection();

        // Assert
        $this->assertEquals($mock, $connector::$sharedConnection);
    }

    /**
     * @expectedException MongoConnectionException
     */
    public function testShouldRaiseExceptionForAConnectionStringInvalid()
    {
        // Arrange
        $connector = IOC::make('Mongolid\Mongolid\Connection\Connection');

        // Act
        $connector->createConnection();
    }
}
