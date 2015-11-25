<?php

namespace Zizaco\Mongolid;

class Sequence
{
    /**
     * Sequences collection name on MongoDB. Default 'mongolid-sequences'
     *
     * @var string
     */
    protected $collection;

    /**
     * MongoDbConnector Instance
     *
     * @var MongoDbConnector
     */
    protected $connector;

    /**
     * MongoDB database name
     *
     * @var string
     */
    protected $database;

    public function __construct(MongoDbConnector $connector, $database, $collection = 'mongolid_sequences')
    {
        $this->connector = $connector;
        $this->collection = $collection;
        $this->database = $database;
    }

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

    /**
     * Get MongoCollection Object
     *
     * @return mixed
     */
    protected function collection()
    {
        return $this->connection->{$this->database}->{$this->collection};
    }

}