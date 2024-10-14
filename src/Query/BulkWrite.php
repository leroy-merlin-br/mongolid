<?php

namespace Mongolid\Query;

use MongoDB\BSON\ObjectId;
use MongoDB\BulkWriteResult;
use MongoDB\Driver\WriteConcern;
use Mongolid\Model\Exception\NoCollectionNameException;
use Mongolid\Model\ModelInterface;

/**
 * This class is meant to provide a better API for handling
 * with bulk operations.
 *
 * It's an incomplete and highly opinionated abstraction.
 */
class BulkWrite
{
    /**
     * Hold bulk write operations to run.
     */
    private array $operations = [];

    public function __construct(
        private ModelInterface $model
    ) {
    }

    public function isEmpty(): bool
    {
        return !$this->operations;
    }

    /**
     * Add an `update` operation to the Bulk, where only one record is updated, by `_id` or `query`.
     * Be aware that working with multiple levels of nesting on `$dataToSet` may have
     * an undesired behavior that could lead to data loss on a specific key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/update/set/#set-top-level-fields
     *
     */
    public function updateOne(
        ObjectId|string|array $filter,
        array $dataToSet,
        array $options = ['upsert' => true],
        string $operator = '$set'
    ): void {
        $filter = is_array($filter) ? $filter : ['_id' => $filter];

        $update = [$operator => $dataToSet];

        $this->operations[] = ['updateOne' => [$filter, $update, $options]];
    }

    /**
     * Execute the BulkWrite, using connection.
     * The collection is inferred from model's collection name.
     *
     * @throws NoCollectionNameException
     */
    public function execute(int $writeConcern = 1): BulkWriteResult
    {
        $collection = $this->model->getCollection();

        $result = $collection->bulkWrite(
            $this->operations,
            ['writeConcern' => new WriteConcern($writeConcern)]
        );

        $this->operations = [];

        return $result;
    }

    public function count(): int
    {
        return count($this->operations);
    }
}
