<?php

use Zizaco\Mongolid\Sequence;
use Mockery as m;

class SequenceTest extends PHPUnit_Framework_TestCase
{
    public function testShouldHaveACollectionName()
    {
        // Set
        $sequence = new Sequence();

        // Expect
        $expected = 'mongolid_sequences';

        // Assert
        $this->assertEquals($expected, $sequence->getCollectionName());
    }

    public function testShouldReturnNextSequenceNumber()
    {
        // Set
        $sequence = m::mock('Zizaco\Mongolid\Sequence' . '[collection,findAndModify]');
        $sequence->shouldAllowMockingProtectedMethods();
        $sequenceName = 'orderId';

        $sequence->shouldReceive('collection')
            ->once()
            ->andReturnSelf();

        $sequence->shouldReceive('findAndModify')
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