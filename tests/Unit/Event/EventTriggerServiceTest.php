<?php
namespace Mongolid\Event;

use Mockery as m;
use Mongolid\TestCase;

class EventTriggerServiceTest extends TestCase
{
    public function testShouldSendTheEventsToTheExternalBuilder()
    {
        // Set
        $builder = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();
        $service->registerEventBuilder($builder);

        // Expectations
        $builder->expects()
            ->fire('foobar', ['answer' => 23], true)
            ->andReturn(true);

        // Actions
        $result = $service->fire('foobar', ['answer' => 23], true);

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldReturnTrueIfThereIsNoExternalBuilder()
    {
        // Set
        $builder = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();

        // Expectations
        $builder->expects()
            ->fire()
            ->never();

        // Actions
        $result = $service->fire('foobar', ['answer' => 23], true);

        // Assertions
        /* without calling registerEventBuilder */
        $this->assertTrue($result);
    }
}
