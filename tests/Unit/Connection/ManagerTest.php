<?php
namespace Mongolid\Connection;

use Illuminate\Container\Container as IlluminateContainer;
use Mockery as m;
use MongoDB\Client;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\TestCase;

final class ManagerTest extends TestCase
{
    public function testShouldAddAndGetConnection(): void
    {
        // Set
        $manager = new Manager();
        $connection = m::mock(Connection::class);
        $client = m::mock(Client::class);

        // Expectations
        $connection
            ->expects('getClient')
            ->withNoArgs()
            ->andReturn($client);

        // Actions
        $manager->setConnection($connection);

        // Assertions
        $this->assertSame($client, $manager->getClient());
    }

    public function testShouldSetEventTrigger(): void
    {
        // Set
        $test = $this;
        $manager = new Manager();
        $container = m::mock(IlluminateContainer::class);
        $eventTrigger = m::mock(EventTriggerInterface::class);

        $this->setProtected($manager, 'container', $container);

        // Expectations
        $expectationCallable = function ($class, $eventService) use ($test, $eventTrigger) {
            $test->assertSame(EventTriggerService::class, $class);
            $dispatcher = $this->getProtected($eventService, 'dispatcher');
            $test->assertSame($eventTrigger, $dispatcher);
        };

        $container
            ->expects('instance')
            ->with(EventTriggerService::class, m::type(EventTriggerService::class))
            ->andReturnUsing($expectationCallable);

        // Actions
        $manager->setEventTrigger($eventTrigger);
    }

    public function testShouldInitializeOnce(): void
    {
        // Set
        $manager = new Manager();

        // Actions
        $this->callProtected($manager, 'init');

        // Assertions
        $this->assertInstanceOf(IlluminateContainer::class, $manager->container);

        // Actions
        $container = $manager->container;
        $this->callProtected($manager, 'init');

        // Assertions
        // Initializes again to make sure that it will not instantiate a new container
        $this->assertSame($container, $manager->container);
    }
}
