<?php

namespace Mongolid\Event;

use Mockery as m;
use Mongolid\TestCase;

final class EventTriggerServiceTest extends TestCase
{
    public function testShouldSendTheEventsToTheExternalDispatcher(): void
    {
        // Set
        $dispatcher = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();
        $service->registerEventDispatcher($dispatcher);

        // Expectations
        $dispatcher
            ->expects('fire')
            ->with('foobar', ['answer' => 23], true)
            ->andReturn(true);

        // Actions
        $result = $service->fire('foobar', ['answer' => 23], true);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldReturnTrueIfThereIsNoExternalDispatcher(): void
    {
        // Set
        $dispatcher = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();

        // Expectations
        $dispatcher
            ->expects('fire')
            ->withNoArgs()
            ->never();

        // Actions
        $result = $service->fire('foobar', ['answer' => 23], true);

        // Assertions
        /* without calling registerEventDispatcher */
        $this->assertTrue($result);
    }
}
