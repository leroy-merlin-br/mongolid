<?php
namespace Mongolid\Container;

use Illuminate\Container\Container as IlluminateContainer;
use Mockery as m;
use Mongolid\TestCase;

class ContainerTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::expects()
            ->flush();

        parent::tearDown();
    }

    public function testShouldCallMethodsProperlyWithNoArguments(): void
    {
        // Set
        $illuminateContainer = m::mock(IlluminateContainer::class);
        Container::setContainer($illuminateContainer);

        // Expectations
        $illuminateContainer->expects()
            ->method()
            ->andReturn(true);

        // Actions
        Container::method();
    }

    public function testShouldCallMethodsProperlyWithArguments(): void
    {
        // Set
        $illuminateContainer = m::mock(IlluminateContainer::class);
        Container::setContainer($illuminateContainer);

        // Expectations
        $illuminateContainer->expects()
            ->method(1, 2, 3)
            ->andReturn(true);

        // Actions
        Container::method(1, 2, 3);
    }
}
