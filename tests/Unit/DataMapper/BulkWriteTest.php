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
use Mongolid\TestCase;

class BulkWriteTest extends TestCase
{
    public function testShouldConstructBulkWriteObject()
    {
        // Arrange
        $entity = m::mock(HasSchemaInterface::class);

        // Expect
        $entity->expects()
            ->getSchema();

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
        $entity->expects()
            ->getSchema();

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
        $entity->expects()
            ->getSchema();

        $mongoBulkWrite->expects()
            ->update(['_id' => $id], ['$set' => $data], ['upsert' => true]);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$entity]);

        $bulkWrite->expects()
            ->getBulkWrite()
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
        $entity->expects()
            ->getSchema();

        $mongoBulkWrite->expects()
            ->update(['_id' => $id], ['$unset' => $data], ['upsert' => true]);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$entity]);

        $bulkWrite->expects()
            ->getBulkWrite()
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
        $entity->expects()
            ->getSchema()
            ->andReturn($schema);

        $pool->expects()
            ->getConnection()
            ->andReturn($connection);

        $connection->expects()
            ->getRawManager()
            ->andReturn($manager);

        $manager->expects()
            ->executeBulkWrite($namespace, $mongoBulkWrite, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$entity]);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->execute();
    }
}
