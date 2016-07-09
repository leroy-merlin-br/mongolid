<?php

use MongoDB\BSON\ObjectID;

class TestCase extends PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     * @return  false
     */
    public function setUp()
    {
        require __DIR__ . '/../bootstrap/bootstrap.php';

        return false;
    }

    /**
     * Assert if two queries are equals. It will compare ObjectIDs within any
     * level of the query and make sure that they are the same.
     *
     * @param  mixed $expectedQuery Correct query.
     * @param  mixed $query         Query being evaluated.
     *
     * @return void
     */
    public function assertMongoQueryEquals($expectedQuery, $query)
    {
        $this->assertEquals($expectedQuery, $query, 'Queries are not equals');

        if (! is_array($expectedQuery)) {
            return;
        }

        foreach ($expectedQuery as $key => $value) {
            if (is_object($value)) {
                $this->assertInstanceOf(get_class($value), $query[$key], 'Type of an object within the query is not equals');

                if (method_exists($value , '__toString')) {
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
     * @param       $obj
     * @param       $method
     * @param array $args
     * @return mixed
     */
    protected function callProtected($obj, $method, $args = array())
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
     * Set a protected property of an object
     *
     * @param  mixed  $obj      Object Instance.
     * @param  string $property Property name.
     * @param  mixed  $value    Value to be set.
     *
     * @return void
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
     * Get a protected property of an object
     *
     * @param  mixed  $obj      Object Instance.
     * @param  string $property Property name.
     *
     * @return mixed Property value.
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
