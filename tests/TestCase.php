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
}
