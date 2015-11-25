<?php

use Zizaco\Mongolid\Sequence;

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
}