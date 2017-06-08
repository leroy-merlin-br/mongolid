<?php

namespace Mongolid\Container;

use Illuminate\Contracts\Container\Container as IlluminateContainer;

/**
 * This class is a simple Facade for a Illuminate\Container\Container
 * in order to use the Container as IOC at all classes.
 */
class Ioc
{
    /**
     * Illuminate instance.
     *
     * @var IlluminateContainer
     */
    protected static $container;

    /**
     * Setter for static::$container.
     *
     * @param IlluminateContainer $container The IoC container that will be used by mongolid.
     *
     * @return void
     */
    public static function setContainer(IlluminateContainer $container)
    {
        static::$container = $container;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method Method that is being called.
     * @param array  $args   Method arguments.
     *
     * @return mixed
     */
    public static function __callStatic(string $method, array $args)
    {
        $instance = static::$container;

        return $instance->$method(...$args);
    }
}
