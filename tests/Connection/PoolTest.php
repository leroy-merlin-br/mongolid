<?php
namespace Mongolid\Connection;

use Mockery as m;
use Mongolid\TestCase;

class PoolTest extends TestCase
{
    public function testShouldGetAConnectionFromPoolIfContainsAny()
    {
        // Arrange
        $pool = new Pool();
        $connQueue = m::mock();
        $connection = m::mock(Connection::class);
        $this->setProtected($pool, 'connections', $connQueue);

        // Act
        $connQueue->expects()
            ->pop()
            ->andReturn($connection);

        $connQueue->expects()
            ->push($connection);

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
        $connQueue->expects()
            ->pop()
            ->andReturn(null);

        $connQueue->expects()
            ->push()
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
        $connQueue->expects()
            ->push($connection);

        // Assert
        $this->assertTrue($pool->addConnection($connection));
    }
}
