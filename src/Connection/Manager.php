<?php
namespace Mongolid\Connection;

use Illuminate\Container\Container as IlluminateContainer;
use MongoDB\Client;
use Mongolid\Container\Container;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

/**
 * Wraps the Mongolid initialization. The main purpose of the Manager is to make
 * it easy to use without any framework.
 *
 * With Manager, you can start using Mongolid with pure PHP by
 * simply calling the setConnection method.
 *
 * @example
 *     (new Mongolid\Connection\Manager)->setConnection(new Connection());
 *     // And then start persisting and querying your models.
 */
class Manager
{
    /**
     * Container being used by Mongolid.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    public $container;

    /**
     * Mongolid connection object.
     *
     * @var Connection
     */
    protected $connection;

    /**
     * @var CacheComponent
     */
    protected $cacheComponent;

    /**
     * Main entry point to opening a connection and start using Mongolid in
     * pure PHP. After adding a connection into the Manager you are ready to
     * persist and query your models.
     *
     * @param Connection $connection connection instance to be used in database interactions
     */
    public function setConnection(Connection $connection): bool
    {
        $this->init();

        $this->connection = $connection;
        $this->container->instance(Connection::class, $this->connection);

        return true;
    }

    /**
     * Get MongoDB client.
     */
    public function getClient(): Client
    {
        $this->init();

        return $this->connection->getClient();
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
     * Initializes the Mongolid manager.
     */
    protected function init()
    {
        if ($this->container) {
            return;
        }

        $this->container = new IlluminateContainer();
        $this->cacheComponent = new CacheComponent();
        $this->container->instance(CacheComponentInterface::class, $this->cacheComponent);

        Container::setContainer($this->container);
    }
}
