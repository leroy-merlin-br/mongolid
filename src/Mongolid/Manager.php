<?php

namespace Mongolid;

use Illuminate\Container\Container;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\DataMapper\DataMapper;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\Schema\Schema;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

/**
 * Wraps the Mongolid initialization. The main purpose of the Manager is to make
 * it easy to use without any framework.
 *
 * With the Mongolid\Manager, you can start using Mongolid with pure PHP by
 * simply calling the addConnection method.
 *
 * @example
 *     (new Mongolid\Manager)->addConnection(new Connection);
 *     // And then start persisting and querying your models.
 */
class Manager
{
    /**
     * Singleton instance of the manager.
     *
     * @var Manager
     */
    protected static $singleton;

    /**
     * Container being used by Mongolid.
     *
     * @var \Illuminate\Contracts\Container
     */
    public $container;

    /**
     * Mongolid connection pool being object.
     *
     * @var Pool
     */
    public $connectionPool;

    /**
     * Mongolid cache component object.
     *
     * @var CacheComponent
     */
    public $cacheComponent;

    /**
     * Stores the schemas that have been registered for later use. This may be
     * useful when using Mongolid DataMapper pattern.
     *
     * @var array
     */
    protected $schemas = [];

    /**
     * Main entry point to openning a connection and start using Mongolid in
     * pure PHP. After adding a connection into the Manager you are ready to
     * persist and query your models.
     *
     * @param Connection $connection connection instance to be used in database interactions
     *
     * @return bool Success
     */
    public function addConnection(Connection $connection): bool
    {
        $this->init();
        $this->connectionPool->addConnection($connection);

        return true;
    }

    /**
     * Get the raw MongoDB connection.
     *
     * @return \MongoDB\Client
     */
    public function getConnection()
    {
        $this->init();

        return $this->connectionPool->getConnection()->getRawConnection();
    }

    /**
     * Sets the event trigger for Mongolid events.
     *
     * @param EventTriggerInterface $eventTrigger external event trigger
     */
    public function setEventTrigger(EventTriggerInterface $eventTrigger)
    {
        $this->init();
        $eventService = new EventTriggerService();
        $eventService->registerEventDispatcher($eventTrigger);

        $this->container->instance(EventTriggerService::class, $eventService);
    }

    /**
     * Allow document Schemas to be registered for later use.
     *
     * @param Schema $schema schema being registered
     */
    public function registerSchema(Schema $schema)
    {
        $this->schemas[$schema->entityClass] = $schema;
    }

    /**
     * Retrieves a DataMapper for the given $entityClass. This can only be done
     * if the Schema for that entity has been previously registered with
     * registerSchema() method.
     *
     * @param string $entityClass class of the entity that needs to be mapped
     *
     * @return DataMapper|null dataMapper configured for the $entityClass
     */
    public function getMapper(string $entityClass)
    {
        if (isset($this->schemas[$entityClass])) {
            $dataMapper = Ioc::make(DataMapper::class);
            $dataMapper->setSchema($this->schemas[$entityClass] ?? null);

            return $dataMapper;
        }
    }

    /**
     * Initializes the Mongolid manager.
     */
    protected function init()
    {
        if ($this->container) {
            return;
        }

        $this->container = new Container();
        $this->connectionPool = new Pool();
        $this->cacheComponent = new CacheComponent();

        $this->container->instance(Pool::class, $this->connectionPool);
        $this->container->instance(CacheComponentInterface::class, $this->cacheComponent);
        Ioc::setContainer($this->container);

        static::$singleton = $this;
    }
}
