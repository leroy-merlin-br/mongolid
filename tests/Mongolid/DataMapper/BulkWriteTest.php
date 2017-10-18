<?php

namespace Mongolid\DataMapper;

use Mockery as m;
use MongoDB\Driver\BulkWrite as MongoBulkWrite;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Connection\Pool;
use Mongolid\Container\Ioc;
use Mongolid\Manager;
use Mongolid\Schema\HasSchemaInterface;
use Mongolid\Schema\Schema;
use TestCase;

class BulkWriteTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
        parent::tearDown();
    }

    public function testShouldConstructBulkWriteObject()
    {
        // Arrange
        $entity = m::mock(HasSchemaInterface::class);

        // Expect
        $entity->shouldReceive('getSchema')
            ->once();

        // Act
        $bulkWrite = new BulkWrite($entity);

        // Assert
        $this->assertInstanceOf(BulkWrite::class, $bulkWrite);
    }

    public function testShouldSetAndGetMongoBulkWrite()
    {
        // Arrange
        $entity = m::mock(HasSchemaInterface::class);
        $mongoBulkWrite = new MongoBulkWrite();

        // Expect
        $entity->shouldReceive('getSchema')
            ->once();

        // Act
        $bulkWrite = new BulkWrite($entity);
        $bulkWrite->setBulkWrite($mongoBulkWrite);

        // Assert
        $this->assertSame($mongoBulkWrite, $bulkWrite->getBulkWrite());
    }

    public function testShouldAddUpdateOneOperationToBulkWrite()
    {
        // Arrange
        $entity = m::mock(HasSchemaInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $id = '123';
        $data = ['name' => 'John'];

        // Expect
        $entity->shouldReceive('getSchema')
            ->once();

        $mongoBulkWrite->shouldReceive('update')
            ->once()
            ->with(['_id' => $id], ['$set' => $data], ['upsert' => true]);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$entity]);

        $bulkWrite->shouldReceive('getBulkWrite')
            ->once()
            ->with()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->updateOne($id, $data);
    }

    public function testShouldUpdateOneWithUnsetOperationToBulkWrite()
    {
        // Arrange
        $entity = m::mock(HasSchemaInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $id = '123';
        $data = ['name' => 'John'];

        // Expect
        $entity->shouldReceive('getSchema')
            ->withNoArgs()
            ->once();

        $mongoBulkWrite->shouldReceive('update')
            ->with(['_id' => $id], ['$unset' => $data], ['upsert' => true])
            ->once();

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$entity]);

        $bulkWrite->shouldReceive('getBulkWrite')
            ->with()
            ->once()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->updateOne($id, $data, ['upsert' => true], '$unset');
    }

    public function testShouldExecuteBulkWrite()
    {
        $entity = m::mock(HasSchemaInterface::class);
        $schema = m::mock(Schema::class);
        $entity->schema = $schema;
        $mongoBulkWrite = m::mock(new MongoBulkWrite());
        $pool = m::mock(Pool::class);
        $connection = m::mock(Connection::class);
        $manager = m::mock(Manager::class);

        $connection->defaultDatabase = 'foo';
        $schema->collection = 'bar';
        $namespace = 'foo.bar';

        Ioc::instance(Pool::class, $pool);

        // Expect
        $entity->shouldReceive('getSchema')
            ->once()
            ->with()
            ->andReturn($schema);

        $pool->shouldReceive('getConnection')
            ->once()
            ->with()
            ->andReturn($connection);

        $connection->shouldReceive('getRawManager')
            ->once()
            ->with()
            ->andReturn($manager);

        $manager->shouldReceive('executeBulkWrite')
            ->once()
            ->with($namespace, $mongoBulkWrite, m::type(WriteConcern::class))
            ->andReturn(true);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$entity]);

        $bulkWrite->shouldReceive('getBulkWrite')
            ->once()
            ->with()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->execute();
    }
}
