<?php namespace Mongolid\Mongolid\Container;

use TestCase;
use Mockery as m;

class IocTest extends TestCase
{
    public function testShouldCallStaticWithTheInstanceSetted()
    {
        // Arrange
        $instance = m::mock('Illuminate\Container\Container');
        Ioc::setContainer($instance);

        // Expect
        $instance->shouldReceive('someMethod')
            ->once()
            ->andReturn('cool');

        // Act
        $result = Ioc::someMethod();

        // Assert
        $this->assertEquals('cool', $result);
    }
}
