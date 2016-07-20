<?php

namespace Mongolid\Event;

use Mockery as m;
use TestCase;

class EventTriggerServiceTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldSendTheEventsToTheExternalDispatcher()
    {
        // Arrange
        $dispatcher = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();

        // Act
        $dispatcher->shouldReceive('fire')
            ->once()
            ->with('foobar', ['answer' => 23], true)
            ->andReturn(true);

        // Assertion
        $service->registerEventDispatcher($dispatcher);
        $this->assertTrue(
            $service->fire('foobar', ['answer' => 23], true)
        );
    }

    public function testShouldReturnTrueIfThereIsNoExternalDispatcher()
    {
        // Arrange
        $dispatcher = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();

        // Act
        $dispatcher->shouldReceive('fire')
            ->never();

        // Assertion
        /* without calling registerEventDispatcher */
        $this->assertTrue(
            $service->fire('foobar', ['answer' => 23], true)
        );
    }
}
