<?php

namespace Mongolid\Container;

use Illuminate\Container\Container as IlluminateContainer;
use Mockery as m;
use Mongolid\TestCase;

final class ContainerTest extends TestCase
{
    public function testShouldCallMethodsProperlyWithNoArguments(): void
    {
        // Set
        $illuminateContainer = m::mock(IlluminateContainer::class);
        Container::setContainer($illuminateContainer);

        // Expectations
        $illuminateContainer
            ->expects('method')
            ->withNoArgs()
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
        $illuminateContainer
            ->expects('method')
            ->with(1, 2, 3)
            ->andReturn(true);

        // Actions
        Container::method(1, 2, 3);
    }

    protected function tearDown(): void
    {
        Container::expects()
            ->flush();

        parent::tearDown();
    }
}
