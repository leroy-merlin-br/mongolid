<?php
namespace Mongolid\Model;

use TestCase;
use Mockery as m;
use Mongolid\Container\Ioc;

class RelationsTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }
}
