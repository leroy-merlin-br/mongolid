<?php

class TestCase extends PHPUnit_Framework_TestCase
{

    /**
     * Setup.
     * @return  false
     */
    public function setUp()
    {
        require __DIR__.'/../bootstrap/bootstrap.php';

        return false;
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
     * Call a protected property of an object
     *
     * @param  mixed $obj
     * @param  string $attribute Property name
     * @param  mixed  $value Value to be set
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
}
