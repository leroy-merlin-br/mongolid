<?php

use Zizaco\Mongolid\Sequence;
use Mockery as m;

class SequenceTest extends PHPUnit_Framework_TestCase
{
    protected $sequence;

    public function tearDown()
    {
         m::close();
    }

    public function testShouldReturnNextSequenceNumber()
    {
        // Set
        $connector = m::mock('Zizaco\Mongolid\MongoDbConnector');
        $mongoCollection = m::mock('MongoCollection');

        $connector->shouldReceive('getConnection')
            ->once()
            ->andReturn($mongoCollection);

        $sequence = m::mock('Zizaco\Mongolid\Sequence' . '[collection]', array($connector, 'database'));
        $sequence->shouldAllowMockingProtectedMethods();

        $sequence->shouldReceive('collection')
            ->once()
            ->andReturn($mongoCollection);

        $mongoCollection->shouldReceive('findAndModify')
            ->once()
            ->with(
                array('_id' => 'orderId'),
                array('$inc' => array('seq' => 1)),
                null,
                array(
                    'new' => true,
                    'upsert' => true
                )
            )
            ->andReturn(array('seq' => 2));

        // Expected
        $this->assertEquals(2, $sequence->getNextValue('orderId'));
    }

    public function testShouldReturnAMongoCollection()
    {
        $connector = m::mock('Zizaco\Mongolid\MongoDbConnector');
        $connection = m::mock('ConnectionMock');
        $database = m::mock('DatabaseMock');
        $collection = m::mock('MongoCollection');

        $connection->database = $database;
        $database->mongolid_sequences = $collection;

        $connector->shouldReceive('getConnection')
            ->once()
            ->andReturn($connection);

        $sequence = m::mock('Zizaco\Mongolid\Sequence' . '[collection]', array($connector, 'database'));

        $this->assertEquals($collection, $sequence->collection());
    }
}