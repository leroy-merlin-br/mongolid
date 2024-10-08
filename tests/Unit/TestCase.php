<?php

declare(strict_types=1);

namespace Mongolid;

use Illuminate\Container\Container as IlluminateContainer;
use Mockery as m;
use Mongolid\Container\Container;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class TestCase extends PHPUnitTestCase
{
    public function assertMongoQueryEquals(array $expectedQuery, array $query): void
    {
        $this->assertEquals($expectedQuery, $query, 'Queries are not equals');

        foreach ($expectedQuery as $key => $value) {
            if (is_object($value)) {
                $this->assertInstanceOf(
                    $value::class,
                    $query[$key],
                    'Type of an object within the query is not equals'
                );

                if (method_exists($value, '__toString')) {
                    $this->assertEquals(
                        (string) $value,
                        (string) $query[$key],
                        'Object within the query is not equals'
                    );
                }
            }

            if (is_array($value)) {
                $this->assertMongoQueryEquals($value, $query[$key]);
            }
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        Container::setContainer(new IlluminateContainer());
    }

    protected function tearDown(): void
    {
        Container::flush();
        m::close();

        parent::tearDown();
    }

    /**
     * Actually runs a protected method of the given object.
     *
     * @throws ReflectionException
     */
    protected function callProtected(object|string $obj, string $method, array $args = []): mixed
    {
        $methodObj = new ReflectionMethod($obj::class, $method);
        $methodObj->setAccessible(true);

        return $methodObj->invokeArgs($obj, $args);
    }

    /**
     * Set a protected property of an object.
     *
     * @throws ReflectionException
     */
    protected function setProtected(object|string $obj, string $property, mixed $value): void
    {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    /**
     * Get a protected property of an object.
     *
     * @throws ReflectionException
     */
    protected function getProtected(object|string $obj, string $property): mixed
    {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);

        return $property->getValue($obj);
    }

    /**
     * Replace instance on Ioc
     */
    protected function instance(string $abstract, object $instance): object
    {
        Container::bind(
            $abstract,
            fn (): object => $instance
        );

        return $instance;
    }
}
