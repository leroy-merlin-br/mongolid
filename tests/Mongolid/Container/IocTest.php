<?php

namespace Mongolid\Container;

use Illuminate\Container\Container;
use Mockery as m;
use TestCase;

class IocTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldCallMethodsPropertlywithNoArgument()
    {
        $container = m::mock(Container::class);

        $container->shouldReceive('method')
            ->once()
            ->with()
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method();
    }

    public function testShouldCallMethodsPropertlywithOneArgument()
    {
        $container = m::mock(Container::class);

        $container->shouldReceive('method')
            ->once()
            ->with(1)
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method(1);
    }

    public function testShouldCallMethodsPropertlywithTwoArgument()
    {
        $container = m::mock(Container::class);

        $container->shouldReceive('method')
            ->once()
            ->with(1, 2)
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method(1, 2);
    }

    public function testShouldCallMethodsPropertlywithThreeArgument()
    {
        $container = m::mock(Container::class);

        $container->shouldReceive('method')
            ->once()
            ->with(1, 2, 3)
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method(1, 2, 3);
    }

    public function testShouldCallMethodsPropertlywithFourArgument()
    {
        $container = m::mock(Container::class);

        $container->shouldReceive('method')
            ->once()
            ->with(1, 2, 3, 4)
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method(1, 2, 3, 4);
    }

    public function testShouldCallMethodsPropertlywithFiveOrMoreArgument()
    {
        $container = m::mock(Container::class);

        $container->shouldReceive('method')
            ->once()
            ->with(1, 2, 3, 4, 5)
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method(1, 2, 3, 4, 5);
    }
}
