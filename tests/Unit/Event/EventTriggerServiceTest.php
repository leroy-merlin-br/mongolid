<?php
namespace Mongolid\Event;

use Mockery as m;
use Mongolid\TestCase;

class EventTriggerServiceTest extends TestCase
{
    public function testShouldSendTheEventsToTheExternalBuilder()
    {
        // Arrange
        $builder = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();

        // Act
        $builder->expects()
            ->fire('foobar', ['answer' => 23], true)
            ->andReturn(true);

        // Assertion
        $service->registerEventBuilder($builder);
        $this->assertTrue(
            $service->fire('foobar', ['answer' => 23], true)
        );
    }

    public function testShouldReturnTrueIfThereIsNoExternalBuilder()
    {
        // Arrange
        $builder = m::mock(EventTriggerInterface::class);
        $service = new EventTriggerService();

        // Act
        $builder->expects()
            ->fire()
            ->never();

        // Assertion
        /* without calling registerEventBuilder */
        $this->assertTrue(
            $service->fire('foobar', ['answer' => 23], true)
        );
    }
}
