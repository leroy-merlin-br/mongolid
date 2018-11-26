<?php
namespace Mongolid\Query;

use Mockery as m;
use MongoDB\Driver\BulkWrite as MongoBulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Model\ModelInterface;
use Mongolid\Schema\AbstractSchema;
use Mongolid\TestCase;

class BulkWriteTest extends TestCase
{
    public function testShouldConstructBulkWriteObject()
    {
        // Arrange
        $model = m::mock(ModelInterface::class);

        // Expect
        $model->expects()
            ->getSchema();

        // Act
        $bulkWrite = new BulkWrite($model);

        // Assert
        $this->assertInstanceOf(BulkWrite::class, $bulkWrite);
    }

    public function testShouldSetAndGetMongoBulkWrite()
    {
        // Arrange
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = new MongoBulkWrite();

        // Expect
        $model->expects()
            ->getSchema();

        // Act
        $bulkWrite = new BulkWrite($model);
        $bulkWrite->setBulkWrite($mongoBulkWrite);

        // Assert
        $this->assertSame($mongoBulkWrite, $bulkWrite->getBulkWrite());
    }

    public function testShouldAddUpdateOneOperationToBulkWrite()
    {
        // Arrange
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $id = '123';
        $data = ['name' => 'John'];

        // Expect
        $model->expects()
            ->getSchema();

        $mongoBulkWrite->expects()
            ->update(['_id' => $id], ['$set' => $data], ['upsert' => true]);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->updateOne($id, $data);
    }

    public function testShouldUpdateOneWithUnsetOperationToBulkWrite()
    {
        // Arrange
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $id = '123';
        $data = ['name' => 'John'];

        // Expect
        $model->expects()
            ->getSchema();

        $mongoBulkWrite->expects()
            ->update(
                m::on(
                    function ($actual) {
                        $this->assertSame(['_id' => '123'], $actual);

                        return true;
                    }
                ),
                ['$unset' => $data],
                ['upsert' => true]
            );

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->updateOne($id, $data, ['upsert' => true], '$unset');
    }

    public function testShouldUpdateOneWithQueryOnFilterToBulkWrite()
    {
        // Arrange
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $query = ['_id' => '123', 'grades.grade' => 85];
        $data = ['grades.std' => 6];

        // Expect
        $model->expects()
            ->getSchema();

        $mongoBulkWrite->expects()
            ->update(
                m::on(
                    function ($actual) use ($query) {
                        $this->assertSame($query, $actual);

                        return true;
                    }
                ),
                ['$unset' => $data],
                ['upsert' => true]
            );

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->updateOne($query, $data, ['upsert' => true], '$unset');
    }

    public function testShouldExecuteBulkWrite()
    {
        $model = m::mock(ModelInterface::class);
        $schema = m::mock(AbstractSchema::class);
        $model->schema = $schema;
        $mongoBulkWrite = m::mock(new MongoBulkWrite());
        $connection = $this->instance(Connection::class, m::mock(Connection::class));
        $manager = m::mock(new Manager());

        $connection->defaultDatabase = 'foo';
        $schema->collection = 'bar';
        $namespace = 'foo.bar';

        // Expect
        $model->expects()
            ->getSchema()
            ->andReturn($schema);

        $connection->expects()
            ->getRawManager()
            ->andReturn($manager);

        $manager->expects()
            ->executeBulkWrite($namespace, $mongoBulkWrite, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Act
        $bulkWrite->execute();
    }
}
