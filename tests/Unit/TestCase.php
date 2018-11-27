<?php
namespace Mongolid;

use Illuminate\Container\Container;
use Mockery as m;
use Mongolid\Container\Ioc;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use ReflectionClass;
use ReflectionMethod;

class TestCase extends PHPUnitTestCase
{
    protected function setUp()
    {
        Ioc::setContainer(new Container());
    }

    protected function tearDown()
    {
        Ioc::flush();
        m::close();
        parent::tearDown();
    }

    /**
     * Assert if two queries are equals. It will compare ObjectIds within any
     * level of the query and make sure that they are the same.
     *
     * @param mixed $expectedQuery correct query
     * @param mixed $query         query being evaluated
     */
    protected function assertMongoQueryEquals($expectedQuery, $query)
    {
        $this->assertEquals($expectedQuery, $query, 'Queries are not equals');

        if (!is_array($expectedQuery)) {
            return;
        }

        foreach ($expectedQuery as $key => $value) {
            if (is_object($value)) {
                $this->assertInstanceOf(
                    get_class($value),
                    $query[$key],
                    'Type of an object within the query is not equals'
                );

                if (method_exists($value, '__toString')) {
                    $this->assertSame(
                        (string) $expectedQuery[$key],
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

    /**
     * Actually runs a protected method of the given object.
     *
     * @param object $obj
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     */
    protected function callProtected($obj, $method, $args = [])
    {
        $methodObj = new ReflectionMethod(get_class($obj), $method);
        $methodObj->setAccessible(true);

        return $methodObj->invokeArgs($obj, $args);
    }

    /**
     * Set a protected property of an object.
     *
     * @param mixed  $obj      object Instance
     * @param string $property property name
     * @param mixed  $value    value to be set
     */
    protected function setProtected($obj, $property, $value): void
    {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    /**
     * Get a protected property of an object.
     *
     * @param mixed  $obj      object Instance
     * @param string $property property name
     *
     * @return mixed property value
     */
    protected function getProtected($obj, $property)
    {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * Replace instance on Ioc
     */
    protected function instance($abstract, $instance)
    {
        Ioc::bind(
            $abstract,
            function () use ($instance) {
                return $instance;
            }
        );

        return $instance;
    }
}
