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
        $params = ['conn/my_db', ['options'], ['driver_opts']];
        $mongoClient = new Client;
        $mongoManager = new Manager('mongodb://localhost:27017');
        $container = m::mock(Container::class);
        Ioc::setContainer($container);

        // Act
        $expectedParams = $params;
        $expectedParams[2]['typeMap'] = [
            'array'    => 'array',
            'document' => 'array',
        ];

        $container->shouldReceive('make')
            ->once()
            ->with(Client::class, $expectedParams)
            ->andReturn($mongoClient);

        $container->shouldReceive('make')
            ->once()
            ->with(Manager::class, $expectedParams)
            ->andReturn($mongoManager);

        // Assert
        $connection = new Connection($params[0], $params[1], $params[2]);
        $this->assertAttributeEquals($mongoClient, 'rawConnection', $connection);
        $this->assertAttributeEquals($mongoManager, 'rawManager', $connection);
        $this->assertAttributeEquals('my_db', 'defaultDatabase', $connection);
    }

    public function testShouldGetRawConnection()
    {
        // Arrange
        $mongoClient = new Client;
        $container = m::mock(Container::class)->makePartial();
        Ioc::setContainer($container);

        // Act
        $container->shouldReceive('make')
            ->once()
            ->with(Client::class, m::any())
            ->andReturn($mongoClient);

        // Assert
        $connection = new Connection();
        $this->assertEquals(
            $mongoClient,
            $connection->getRawConnection()
        );
    }

    public function testShouldGetRawManager()
    {
        // Arrange
        $mongoManager = new Manager('mongodb://localhost:27017');
        $container = m::mock(Container::class)->makePartial();
        Ioc::setContainer($container);

        // Act
        $container->shouldReceive('make')
            ->once()
            ->with(Manager::class, m::any())
            ->andReturn($mongoManager);

        // Assert
        $connection = new Connection();

        $this->assertEquals(
            $mongoManager,
            $connection->getRawManager()
        );
    }
}
