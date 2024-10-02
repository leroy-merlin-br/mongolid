<?php

namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use MongoDB\BulkWriteResult;
use Mongolid\Query\BulkWrite;
use Mongolid\Tests\Stubs\ReferencedUser;

final class BulkWriteTest extends IntegrationTestCase
{
    public function testShouldRunMultipleUpdateOperations(): void
    {
        $bob = $this->createUser('Bob');
        $john = $this->createUser('John');
        $mary = $this->createUser('Mary');

        $bulkWrite = new BulkWrite(new ReferencedUser());

        $this->assertTrue($bulkWrite->isEmpty());

        $bulkWrite->updateOne(
            ['_id' => $bob->_id],
            ['name' => 'Bulk Updated Bob!']
        );
        $bulkWrite->updateOne(
            ['_id' => $john->_id],
            ['name' => 'Bulk Updated John!']
        );
        $bulkWrite->updateOne(
            ['_id' => $mary->_id],
            ['name' => 'Bulk Updated Mary!']
        );
        $bulkWrite->updateOne(
            ['_id' => $bob->_id],
            ['delete_this' => ''],
            [],
            '$unset'
        );
        $bulkWrite->updateOne(
            ['_id' => $john->_id],
            ['delete_this' => ''],
            [],
            '$unset'
        );
        $bulkWrite->updateOne(
            ['_id' => $mary->_id],
            ['delete_this' => ''],
            [],
            '$unset'
        );

        $this->assertFalse($bulkWrite->isEmpty());

        // Before running
        $this->assertSame('Bob', $bob->name);
        $this->assertSame('John', $john->name);
        $this->assertSame('Mary', $mary->name);

        $this->assertSame('xxxxx', $bob->delete_this);
        $this->assertSame('xxxxx', $john->delete_this);
        $this->assertSame('xxxxx', $mary->delete_this);

        // Runs it
        $result = $bulkWrite->execute();

        $this->assertTrue($bulkWrite->isEmpty());

        $this->assertInstanceOf(BulkWriteResult::class, $result);
        $this->assertTrue($result->isAcknowledged());
        $this->assertSame(6, $result->getModifiedCount());

        // Refresh models
        $bob = $bob->first($bob->_id);
        $john = $john->first($john->_id);
        $mary = $mary->first($mary->_id);

        // After running
        $this->assertSame('Bulk Updated Bob!', $bob->name);
        $this->assertSame('Bulk Updated John!', $john->name);
        $this->assertSame('Bulk Updated Mary!', $mary->name);

        $this->assertNull($bob->delete_this);
        $this->assertNull($john->delete_this);
        $this->assertNull($mary->delete_this);
    }

    private function createUser(string $name): ReferencedUser
    {
        $user = new ReferencedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $user->delete_this = 'xxxxx';
        $this->assertTrue($user->save());

        return $user;
    }
}
