<?php

namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Stubs\ReferencedUser;

final class RewindableCursorTest extends IntegrationTestCase
{
    public function testCursorShouldBeRewindableAndSerializable(): void
    {
        $this->createUser('Bob');
        $this->createUser('Mary');
        $this->createUser('John');
        $this->createUser('Jane');

        $cursor = ReferencedUser::all();

        // exhaust cursor
        foreach ($cursor as $user) {
            $this->assertInstanceOf(ReferencedUser::class, $user);
        }

        // try again
        foreach ($cursor as $user) {
            $this->assertInstanceOf(ReferencedUser::class, $user);
        }

        // rewind and try again
        $cursor->rewind();
        foreach ($cursor as $user) {
            $this->assertInstanceOf(ReferencedUser::class, $user);
        }

        // serializing
        $newCursor = unserialize(serialize($cursor));
        foreach ($newCursor as $user) {
            $this->assertInstanceOf(ReferencedUser::class, $user);
        }
    }

    private function createUser(string $name): ReferencedUser
    {
        $user = new ReferencedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }
}
