<?php
namespace Mongolid\Query;

use Mockery as m;
use MongoDB\Driver\BulkWrite as MongoBulkWrite;
use MongoDB\Driver\Manager;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Model\ModelInterface;
use Mongolid\TestCase;

class BulkWriteTest extends TestCase
{
    public function testShouldConstructBulkWriteObject()
    {
        // Arrange
        $model = m::mock(ModelInterface::class);

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

        // Act
        $bulkWrite = new BulkWrite($model);
        $bulkWrite->setBulkWrite($mongoBulkWrite);

        // Assert
        $this->assertSame($mongoBulkWrite, $bulkWrite->getBulkWrite());
    }

    public function testShouldAddUpdateOneOperationToBulkWrite()
    {
        // Set
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $id = '123';
        $data = ['name' => 'John'];

        // Expectations
        $mongoBulkWrite->expects()
            ->update(['_id' => $id], ['$set' => $data], ['upsert' => true]);

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Actions
        $bulkWrite->updateOne($id, $data);
    }

    public function testShouldUpdateOneWithUnsetOperationToBulkWrite()
    {
        // Set
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $id = '123';
        $data = ['name' => 'John'];

        // Expectations
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

        // Actions
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
        $mongoBulkWrite = m::mock(new MongoBulkWrite());
        $connection = $this->instance(Connection::class, m::mock(Connection::class));
        $manager = m::mock(new Manager());

        $connection->defaultDatabase = 'foo';
        $namespace = 'foo.bar';

        // Expect
        $connection->expects()
            ->getRawManager()
            ->andReturn($manager);

        $model->expects()
            ->getCollectionName()
            ->andReturn('bar');

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
