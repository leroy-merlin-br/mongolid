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
        // Set
        $model = m::mock(ModelInterface::class);

        // Actions
        $bulkWrite = new BulkWrite($model);

        // Assertions
        $this->assertInstanceOf(BulkWrite::class, $bulkWrite);
    }

    public function testShouldSetAndGetMongoBulkWrite()
    {
        // Set
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = new MongoBulkWrite();

        // Actions
        $bulkWrite = new BulkWrite($model);
        $bulkWrite->setBulkWrite($mongoBulkWrite);

        // Assertions
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

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

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

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Actions
        $bulkWrite->updateOne($id, $data, ['upsert' => true], '$unset');
    }

    public function testShouldUpdateOneWithQueryOnFilterToBulkWrite()
    {
        // Set
        $model = m::mock(ModelInterface::class);
        $mongoBulkWrite = m::mock(new MongoBulkWrite());

        $query = ['_id' => '123', 'grades.grade' => 85];
        $data = ['grades.std' => 6];

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        // Expectations
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

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Actions
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

        $bulkWrite = m::mock(BulkWrite::class.'[getBulkWrite]', [$model]);

        // Expectations
        $connection->expects()
            ->getRawManager()
            ->andReturn($manager);

        $model->expects()
            ->getCollectionName()
            ->andReturn('bar');

        $manager->expects()
            ->executeBulkWrite($namespace, $mongoBulkWrite, ['writeConcern' => new WriteConcern(1)])
            ->andReturn(true);

        $bulkWrite->expects()
            ->getBulkWrite()
            ->andReturn($mongoBulkWrite);

        // Actions
        $bulkWrite->execute();
    }
}
