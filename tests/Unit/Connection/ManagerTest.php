<?php
namespace Mongolid\Connection;

use Illuminate\Container\Container;
use Mockery as m;
use MongoDB\Client;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\Schema\Schema;
use Mongolid\TestCase;
use Mongolid\Util\CacheComponentInterface;

class ManagerTest extends TestCase
{
    protected function tearDown()
    {
        $this->setProtected(Manager::class, 'singleton', null);
        parent::tearDown();
    }

    public function testShouldAddAndGetConnection()
    {
        // Arrange
        $manager = new Manager();
        $connection = m::mock(Connection::class);
        $rawConnection = m::mock(Client::class);

        // Expect
        $connection->expects()
            ->getRawConnection()
            ->andReturn($rawConnection);

        // Act
        $manager->setConnection($connection);

        // Assert
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
        $container->expects()
            ->instance(EventTriggerService::class, m::type(EventTriggerService::class))
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
        $dataMapper = $this->instance(DataMapper::class, m::mock(DataMapper::class)->makePartial());

        $schema->entityClass = 'Bacon';

        // Act
        $manager->registerSchema($schema);
        $result = $manager->getMapper('Bacon');

        // Assert
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
        $this->assertAttributeInstanceOf(CacheComponentInterface::class, 'cacheComponent', $manager);

        $container = $manager->container;
        $this->callProtected($manager, 'init');
        // Initializes again to make sure that it will not instantiate a new container
        $this->assertAttributeEquals($container, 'container', $manager);
    }
}
