<?php
namespace Mongolid\Tests\Integration;

use Mongolid\TestCase;
use Mongolid\Tests\Util\DropDatabaseTrait;
use Mongolid\Tests\Util\SetupPoolTrait;

class IntegrationTestCase extends TestCase
{
    use DropDatabaseTrait;
    use SetupPoolTrait;

    protected function setUp()
    {
        parent::setUp();
        $host = env('DB_HOST', 'localhost');
        $database = env('DB_DATABASE', 'testing');

        $this->setupPool($host, $database);
        $this->dropDatabase();
    }

    protected function tearDown()
    {
        $this->dropDatabase();
        parent::tearDown();
    }
}
