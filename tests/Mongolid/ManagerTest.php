<?php

namespace Mongolid;

use Illuminate\Container\Container;
use Mockery as m;
use MongoDB\Client;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\Schema\Schema;
use Mongolid\Util\CacheComponentInterface;
use TestCase;

class ManagerTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
        $this->setProtected(Manager::class, 'singleton', null);
    }

    public function testShouldAddAndGetConnection()
    {
        // Arrange
        $manager = new Manager();
        $connection = m::mock(Connection::class);
        $rawConnection = m::mock(Client::class);

        // Act
        $connection->shouldReceive('getRawConnection')
            ->andReturn($rawConnection);

        // Assert
        $manager->addConnection($connection);
        $this->assertEquals($rawConnection, $manager->getConnection());
    }

    public function testShouldSetEventTrigger()
    {
        // Arrange
        $test = $this;
        $manager = new Manager();
        $container = m::mock(Container::class);
        $eventTrigger = m::mock(EventTriggerInterface::class);

        $this->setProtected($manager, 'container', $container);

        // Act
        $container->shouldReceive('instance')
            ->once()
            ->andReturnUsing(function ($class, $eventService) use ($test, $eventTrigger) {
                $test->assertEquals(EventTriggerService::class, $class);
                $test->assertAttributeEquals($eventTrigger, 'dispatcher', $eventService);
            });

        // Assert
        $manager->setEventTrigger($eventTrigger);
    }

    public function testShouldRegisterSchema()
    {
        // Arrange
        $manager = new Manager();
        $schema = m::mock(Schema::class);
        $schema->entityClass = 'Bacon';

        // Assert
        $manager->registerSchema($schema);
        $this->assertAttributeEquals(
            ['Bacon' => $schema],
            'schemas',
            $manager
        );
    }

    public function testShouldGetDataMapperForEntitiesWithRegisteredSchemas()
    {
        // Arrange
        $manager = new Manager();
        $schema = m::mock(Schema::class);
        $dataMapper = m::mock(DataMapper::class)->makePartial();

        $schema->entityClass = 'Bacon';

        // Act
        Ioc::instance(DataMapper::class, $dataMapper);

        // Assert
        $manager->registerSchema($schema);
        $result = $manager->getMapper('Bacon');

        $this->assertEquals($dataMapper, $result);
        $this->assertAttributeEquals($schema, 'schema', $result);
    }

    public function testShouldNotGetDataMapperForUnknownEntities()
    {
        // Arrange
        $manager = new Manager();

        // Assert
        $result = $manager->getMapper('Unknow');
        $this->assertNull($result);
    }

    public function testShouldInitializeOnce()
    {
        // Arrange
        $manager = new Manager();
        $this->callProtected($manager, 'init');

        // Assertion
        $this->assertAttributeEquals($manager, 'singleton', Manager::class);
        $this->assertAttributeInstanceOf(Container::class, 'container', $manager);
        $this->assertAttributeInstanceOf(Pool::class, 'connectionPool', $manager);
        $this->assertAttributeInstanceOf(CacheComponentInterface::class, 'cacheComponent', $manager);

        $container = $manager->container;
        $this->callProtected($manager, 'init');
        // Initializes again to make sure that it will not instantiate a new container
        $this->assertAttributeEquals($container, 'container', $manager);
    }
}
