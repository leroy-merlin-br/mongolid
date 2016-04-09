<?php

namespace Mongolid;

use Illuminate\Container\Container;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Event\EventTriggerInterface;
use Mongolid\Event\EventTriggerService;

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
 *
 * @package Mongolid
 */
class Manager
{
    /**
     * Singleton instance of the manager
     * @var Manager
     */
    protected static $singleton;

    /**
     * Container being used by Mongolid
     * @var \Illuminate\Contracts\Container\Container
     */
    public $container;

    /**
     * Mongolid connection pool being object
     * @var Pool
     */
    public $connectionPool;

    /**
     * Main entry point to openning a connection and start using Mongolid in
     * pure PHP. After adding a connection into the Manager you are ready to
     * persist and query your models.
     *
     * @param  Connection $connection Connection instance to be used in database interactions.
     *
     * @return boolean Success
     */
    public function addConnection(Connection $connection): bool
    {
        $this->init();
        $this->connectionPool->addConnection($connection);

        return true;
    }

    /**
     * Get the raw MongoDB connection
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
     * @param EventTriggerInterface $eventTrigger External event trigger.
     *
     * @return void
     */
    public function setEventTrigger(EventTriggerInterface $eventTrigger)
    {
        $this->init();
        $eventService = new EventTriggerService;
        $eventService->registerEventDispatcher($eventTrigger);

        $this->container->instance(EventTriggerService::class, $eventService);
    }

    /**
     * Initializes the Mongolid manager
     *
     * @return void
     */
    protected function init()
    {
        if ($this->container) {
            return;
        }

        $this->container      = new Container;
        $this->connectionPool = new Pool;

        $this->container->instance(Pool::class, $this->connectionPool);
        Ioc::setContainer($this->container);

        static::$singleton = $this;
    }
}
