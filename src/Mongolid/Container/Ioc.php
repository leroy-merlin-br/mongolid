<?php namespace Mongolid\Container;

use Illuminate\Container\Container as IlluminateContainer;

/**
 * This class is a simple Facade for a Illuminate\Container\Container
 * in order to use the Container as IOC at all classes.
 */
class Ioc
{
    /**
     * Illuminate instance.
     * @var Illuminate\Container\Container
     */
    protected static $container;

    /**
     * Setter for static::$container.
     * @param Container $container
     */
    public static function setContainer(IlluminateContainer $container)
    {
        static::$container = $container;
    }

    /**
     * Handle dynamic, static calls to the object.
     *
     * @param  string  $method
     * @param  array   $args
     * @return mixed
     */
    public static function __callStatic($method, $args)
    {
        $instance = static::$container;

        switch (count($args))
        {
            case 0:
                return $instance->$method();

            case 1:
                return $instance->$method($args[0]);

            case 2:
                return $instance->$method($args[0], $args[1]);

            case 3:
                return $instance->$method($args[0], $args[1], $args[2]);

            case 4:
                return $instance->$method($args[0], $args[1], $args[2], $args[3]);

            default:
                return call_user_func_array(array($instance, $method), $args);
        }
    }
}
