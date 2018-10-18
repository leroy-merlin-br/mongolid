<?php
namespace Mongolid\Container;

use Illuminate\Container\Container;
use Mockery as m;
use Mongolid\TestCase;

class IocTest extends TestCase
{
    protected function tearDown()
    {
        Ioc::expects()
            ->flush();

        parent::tearDown();
    }

    public function testShouldCallMethodsProperlyWithNoArguments()
    {
        $container = m::mock(Container::class);

        $container->expects()
            ->method()
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method();
    }

    public function testShouldCallMethodsProperlyWithArguments()
    {
        $container = m::mock(Container::class);

        $container->expects()
            ->method(1, 2, 3)
            ->andReturn(true);

        Ioc::setContainer($container);

        Ioc::method(1, 2, 3);
    }
}
