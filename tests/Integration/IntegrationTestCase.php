<?php
namespace Mongolid\Tests\Integration;

use Mongolid\TestCase;
use Mongolid\Tests\Util\DropDatabaseTrait;
use Mongolid\Tests\Util\SetupConnectionTrait;

class IntegrationTestCase extends TestCase
{
    use DropDatabaseTrait;
    use SetupConnectionTrait;

    public function setUp(): void
    {
        parent::setUp();
        $host = getenv('DB_HOST') ?: 'db';
        $database = getenv('DB_DATABASE') ?: 'testing';

        $this->setupConnection($host, $database);
        $this->dropDatabase();
    }

    protected function tearDown(): void
    {
        $this->dropDatabase();
        parent::tearDown();
    }
}
