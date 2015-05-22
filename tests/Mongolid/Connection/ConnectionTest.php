<?php
namespace Mongolid\Connection;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

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
        $params      =  ['conn', ['options'], ['driver_opts']];
        $mongoClient = m::mock('MongoClient');
        $container   = m::mock('Illuminate\Container\Container');
        Ioc::setContainer($container);

        // Act
        $container->shouldReceive('make')
            ->once()
            ->with('MongoClient', $params)
            ->andReturn($mongoClient);

        // Assert
        $connection = new Connection($params[0], $params[1], $params[2]);
        $this->assertAttributeEquals($mongoClient, 'rawConnection', $connection);
    }

    public function testShouldGetRawConnection()
    {
        // Arrange
        $mongoClient = m::mock('MongoClient');
        $container   = m::mock('Illuminate\Container\Container');
        Ioc::setContainer($container);

        // Act
        $container->shouldReceive('make')
            ->once()
            ->andReturn($mongoClient);

        // Assert
        $connection = new Connection();
        $this->assertEquals(
            $mongoClient,
            $connection->getRawConnection()
        );
    }
}
