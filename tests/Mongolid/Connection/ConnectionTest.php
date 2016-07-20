<?php

namespace Mongolid\Connection;

use Illuminate\Container\Container;
use Mockery as m;
use MongoDB\Client;
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
        $mongoClient = m::mock(Client::class);
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

        // Assert
        $connection = new Connection($params[0], $params[1], $params[2]);
        $this->assertAttributeEquals($mongoClient, 'rawConnection', $connection);
        $this->assertAttributeEquals('my_db', 'defaultDatabase', $connection);
    }

    public function testShouldGetRawConnection()
    {
        // Arrange
        $mongoClient = m::mock(Client::class);
        $container = m::mock(Container::class);
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
