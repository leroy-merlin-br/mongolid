<?php namespace Mongolid\Mongolid\Container;

use TestCase;
use Mockery as m;

class IocTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldCallStaticWithTheInstanceSetted()
    {
        // Arrange
        $instance = m::mock('Illuminate\Container\Container');
        Ioc::setContainer($instance);

        // Expect
        $instance->shouldReceive('someMethod')
            ->times(7)
            ->andReturn('value returned');

        // Act
        $result = Ioc::someMethod(1);
        $result = Ioc::someMethod(1, 2);
        $result = Ioc::someMethod(1, 2, 3);
        $result = Ioc::someMethod(1, 2, 3, 4);
        $result = Ioc::someMethod(1, 2, 3, 4, 5);
        $result = Ioc::someMethod(1, 2, 3, 4, 5, 6);
        $result = Ioc::someMethod();

        // Assert
        $this->assertEquals('value returned', $result);
    }
}
