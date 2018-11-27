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
        // Set
        $container = m::mock(Container::class);
        Ioc::setContainer($container);

        // Expectations
        $container->expects()
            ->method()
            ->andReturn(true);

        // Actions
        Ioc::method();
    }

    public function testShouldCallMethodsProperlyWithArguments()
    {
        // Set
        $container = m::mock(Container::class);
        Ioc::setContainer($container);

        // Expectations
        $container->expects()
            ->method(1, 2, 3)
            ->andReturn(true);

        // Actions
        Ioc::method(1, 2, 3);
    }
}
