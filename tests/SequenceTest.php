<?php

use Zizaco\Mongolid\Sequence;
use Mockery as m;

class SequenceTest extends PHPUnit_Framework_TestCase
{

    public function testShouldReturnNextSequenceNumber()
    {
        // Set
        $connector = m::mock('Zizaco\Mongolid\MongoDbConnector');
        $collection = m::mock('MongoCollection');

        $sequence = m::mock('Zizaco\Mongolid\Sequence' . '[collection]', array($connector, 'database'));
        $sequence->shouldAllowMockingProtectedMethods();

        $sequence->shouldReceive('collection')
            ->once()
            ->andReturn($collection);

        $sequence->shouldAllowMockingProtectedMethods();

        $sequenceName = 'orderId';

        $collection->shouldReceive('findAndModify')
            ->once()
            ->with(
                array('_id' => $sequenceName),
                array('$inc' => array('seq' => 1)),
                null,
                array(
                    'new' => true,
                    'upsert' => true
                )
            )
            ->andReturn(array('seq' => 2));

        // Expected
        $this->assertEquals(2, $sequence->getNextValue($sequenceName));
    }

}