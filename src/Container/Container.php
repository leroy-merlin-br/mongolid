<?php

declare(strict_types=1);

namespace Mongolid\Container;

use Illuminate\Contracts\Container\Container as IlluminateContainer;

/**
 * This class is a simple Facade for an Illuminate\Container\Container
 * in order to use the Container as IOC at all classes.
 *
 * @mixin \Illuminate\Container\Container
 */
class Container
{
    protected static ?IlluminateContainer $container = null;

    /**
     * Setter for static::$container.
     */
    public static function setContainer(IlluminateContainer $container): void
    {
        static::$container = $container;
    }

    /**
     * Handle dynamic, static calls to the object.
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        $instance = static::$container;

        return $instance->$method(...$args);
    }
}
