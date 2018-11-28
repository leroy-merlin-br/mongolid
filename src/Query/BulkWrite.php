<?php
namespace Mongolid\Query;

use MongoDB\BSON\ObjectId;
use MongoDB\Driver\BulkWrite as MongoBulkWrite;
use MongoDB\Driver\WriteConcern;
use Mongolid\Connection\Connection;
use Mongolid\Container\Ioc;
use Mongolid\Model\ModelInterface;

/**
 * This class is meant to provide a better API for handling
 * with bulk operations.
 *
 * It's an incomplete and highly opinionated abstraction
 * but yet flexible, since you are able to access the
 * driver's API.
 */
class BulkWrite
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var MongoBulkWrite
     */
    protected $bulkWrite;

    /**
     * @var ModelInterface
     */
    private $model;

    public function __construct(ModelInterface $model)
    {
        $this->setBulkWrite(new MongoBulkWrite(['ordered' => false]));
        $this->model = $model;
    }

    /**
     * Get the BulkWrite object to perform other operations
     * not covered by this class.
     *
     * @return MongoBulkWrite
     */
    public function getBulkWrite()
    {
        return $this->bulkWrite;
    }

    /**
     * Set BulkWrite object that will receive all operations
     * and later be executed.
     *
     * @return $this
     */
    public function setBulkWrite(MongoBulkWrite $bulkWrite)
    {
        $this->bulkWrite = $bulkWrite;

        return $this;
    }

    /**
     * Add an `update` operation to the Bulk, where only one record is updated, by `_id` or `query`.
     * Be aware that working with multiple levels of nesting on `$dataToSet` may have
     * an undesired behavior that could lead to data loss on a specific key.
     *
     * @see https://docs.mongodb.com/manual/reference/operator/update/set/#set-top-level-fields
     *
     * @param ObjectId|string|array $filter
     * @param array                 $dataToSet
     * @param array                 $options
     */
    public function updateOne(
        $filter,
        array $dataToSet,
        array $options = ['upsert' => true],
        string $operator = '$set'
    ) {
        $filter = is_array($filter) ? $filter : ['_id' => $filter];

        return $this->getBulkWrite()->update(
            $filter,
            [$operator => $dataToSet],
            $options
        );
    }

    /**
     * Execute the BulkWrite, using connection.
     * The collection is inferred from model's collection name.
     *
     * @throws \Mongolid\Model\Exception\NoCollectionNameException
     *
     * @return \MongoDB\Driver\WriteResult
     */
    public function execute(int $writeConcern = 1)
    {
        $connection = Ioc::make(Connection::class);
        $manager = $connection->getManager();

        $namespace = $connection->defaultDatabase.'.'.$this->model->getCollectionName();

        return $manager->executeBulkWrite(
            $namespace,
            $this->getBulkWrite(),
            ['writeConcern' => new WriteConcern($writeConcern)]
        );
    }
}
