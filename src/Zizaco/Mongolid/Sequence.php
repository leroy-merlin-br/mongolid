<?php

namespace Zizaco\Mongolid;

class Sequence extends Model
{
    /**
     * Sequences collection name on MongoDB
     *
     * @var string
     */
    protected $collection = 'mongolid_sequences';

    /**
     * Get next value for the sequence
     *
     * @param string $sequenceName
     * @return int
     */
    public function getNextValue($sequenceName)
    {
        $sequenceValue = $this->collection()->findAndModify(
            array('_id' => $sequenceName),
            array('$inc' => array('seq' => 1)),
            null,
            array(
                'new' => true,
                'upsert' => true
            )
        );

        return $sequenceValue['seq'];
    }

}