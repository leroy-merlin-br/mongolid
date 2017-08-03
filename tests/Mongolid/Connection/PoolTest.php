<?php

namespace Mongolid\Connection;

use Mockery as m;
use TestCase;

class PoolTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldGetAConnectionFromPoolIfContainsAny()
    {
        // Arrange
        $pool = new Pool();
        $connQueue = m::mock();
        $connection = m::mock(Connection::class);
        $this->setProtected($pool, 'connections', $connQueue);

        // Act
        $connQueue->shouldReceive('pop')
            ->once()
            ->andReturn($connection);

        $connQueue->shouldReceive('push')
            ->once()
            ->with($connection);

        // Assert
        $this->assertEquals($connection, $pool->getConnection());
    }

    public function testShouldGetNullConnectionFromPoolIfItsEmpty()
    {
        // Arrange
        $pool = new Pool();
        $connQueue = m::mock();
        $this->setProtected($pool, 'connections', $connQueue);

        // Act
        $connQueue->shouldReceive('pop')
            ->once()
            ->andReturn(null);

        $connQueue->shouldReceive('push')
            ->never();

        // Assert
        $this->assertNull($pool->getConnection());
    }

    public function testShouldAddConnectionToPool()
    {
        // Arrange
        $pool = new Pool();
        $connQueue = m::mock();
        $connection = m::mock(Connection::class);
        $this->setProtected($pool, 'connections', $connQueue);

        // Act
        $connQueue->shouldReceive('push')
            ->once()
            ->with($connection);

        // Assert
        $this->assertTrue($pool->addConnection($connection));
    }
}
