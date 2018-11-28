<?php
namespace Mongolid\Connection;

use Illuminate\Container\Container as IlluminateContainer;
use Mockery as m;
use MongoDB\Client;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\TestCase;

class ManagerTest extends TestCase
{
    protected function tearDown()
    {
        $this->setProtected(Manager::class, 'singleton', null);
        parent::tearDown();
    }

    public function testShouldAddAndGetConnection()
    {
        // Set
        $manager = new Manager();
        $connection = m::mock(IlluminateContainer::class);
        $client = m::mock(Client::class);

        // Expectations
        $connection->expects()
            ->getClient()
            ->andReturn($client);

        // Actions
        $manager->setConnection($connection);

        // Assertions
        $this->assertSame($client, $manager->getClient());
    }

    public function testShouldSetEventTrigger()
    {
        // Set
        $test = $this;
        $manager = new Manager();
        $container = m::mock(IlluminateContainer::class);
        $eventTrigger = m::mock(EventTriggerInterface::class);

        $this->setProtected($manager, 'container', $container);

        // Expectations
        $container->expects()
            ->instance(EventTriggerService::class, m::type(EventTriggerService::class))
            ->andReturnUsing(function ($class, $eventService) use ($test, $eventTrigger) {
                $test->assertSame(EventTriggerService::class, $class);
                $test->assertAttributeSame($eventTrigger, 'dispatcher', $eventService);
            });

        // Actions
        $manager->setEventTrigger($eventTrigger);
    }

    public function testShouldInitializeOnce()
    {
        // Set
        $manager = new Manager();

        // Actions
        $this->callProtected($manager, 'init');

        // Assertions
        $this->assertAttributeSame($manager, 'singleton', Manager::class);
        $this->assertAttributeInstanceOf(IlluminateContainer::class, 'container', $manager);

        // Actions
        $container = $manager->container;
        $this->callProtected($manager, 'init');

        // Assertions
        // Initializes again to make sure that it will not instantiate a new container
        $this->assertAttributeSame($container, 'container', $manager);
    }
}
