<?php

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        require __DIR__.'/../bootstrap/bootstrap.php';
    }

    /**
     * Assert if two queries are equals. It will compare ObjectIDs within any
     * level of the query and make sure that they are the same.
     *
     * @param mixed $expectedQuery correct query
     * @param mixed $query         query being evaluated
     */
    public function assertMongoQueryEquals($expectedQuery, $query)
    {
        $this->assertEquals($expectedQuery, $query, 'Queries are not equals');

        if (!is_array($expectedQuery)) {
            return;
        }

        foreach ($expectedQuery as $key => $value) {
            if (is_object($value)) {
                $this->assertInstanceOf(get_class($value), $query[$key], 'Type of an object within the query is not equals');

                if (method_exists($value, '__toString')) {
                    $this->assertEquals((string) $expectedQuery[$key], (string) $query[$key], 'Object within the query is not equals');
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
     * @param       $obj
     * @param       $method
     * @param array $args
     *
     * @return mixed
     */
    protected function callProtected($obj, $method, $args = [])
    {
        $methodObj = new ReflectionMethod(get_class($obj), $method);
        $methodObj->setAccessible(true);

        if (is_object($args)) {
            $args = [$args];
        } else {
            $args = (array) $args;
        }

        return $methodObj->invokeArgs($obj, $args);
    }

    /**
     * Set a protected property of an object.
     *
     * @param mixed  $obj      object Instance
     * @param string $property property name
     * @param mixed  $value    value to be set
     */
    protected function setProtected($obj, $property, $value)
    {
        $class = new ReflectionClass($obj);
        $property = $class->getProperty($property);
        $property->setAccessible(true);

        if (is_string($obj)) { // static
            $property->setValue($value);

            return;
        }

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

        if (is_string($obj)) { // static
            return $property->getValue();
        }

        return $property->getValue($obj);
    }
}
