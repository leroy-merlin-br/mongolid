<?php

namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Stubs\EmbeddedUser;
use Mongolid\Util\CacheComponent;
use Mongolid\Util\CacheComponentInterface;

class CacheTest extends IntegrationTestCase
{
    public function testShouldGetFirstModelFromCache(): void
    {
        // Set
        $this->instance(CacheComponentInterface::class, new CacheComponent());
        $user = $this->createUser('John');

        // Actions
        $user = EmbeddedUser::first($user->_id, [], true);

        // Clone the user, so it will generate a new instance id
        // and saving it on database.
        $myUser = clone $user;
        $myUser->name = 'Bob';
        $myUser->save();

        // Getting the user again
        $anotherUser = EmbeddedUser::first($user->_id, [], true);

        // Asserting that we're still getting John
        // Even tough he was changed.
        $this->assertSame('John', $anotherUser->name);
        $this->assertSame($myUser->_id, $anotherUser->_id);

        // Getting the user again, but now, without cache.
        $userWithoutCache = EmbeddedUser::first($user->_id);

        // Asserting that now, the user is indeed Bob
        $this->assertSame('Bob', $userWithoutCache->name);
    }

    public function testShouldGetUsersFromCache(): void
    {
        // Set
        $this->instance(CacheComponentInterface::class, new CacheComponent());
        $this->createUser('John');

        // Actions
        $users = EmbeddedUser::where(['age' => 30], [], true);
        $this->assertCount(1, $users->all());

        // Create a user after the cursor was cached.
        $this->createUser('Bob');

        // Getting users again
        $newUsers = EmbeddedUser::where(['age' => 30], [], true);

        // Assert that the amount of users didn't change.
        $this->assertCount(1, $newUsers->all());

        // Getting users, but this time without hitting cache.
        $usersWithoutCache = EmbeddedUser::where(['age' => 30]);

        // Assert that now it is taking Bob into consideration
        $this->assertCount(2, $usersWithoutCache->all());
    }

    public function testShouldNotGetTheSameUsersIfQueryIsDifferent(): void
    {
        // Set
        $this->instance(CacheComponentInterface::class, new CacheComponent());
        $this->createUser('John');

        // Actions
        $users = EmbeddedUser::where(['age' => 30], [], true);
        $this->assertCount(1, $users->all());

        // Create a user after the cursor was cached.
        $this->createUser('Bob');

        // Getting users again
        $newUsers = EmbeddedUser::where([], [], true);

        // Assert that the amount of users didn't change.
        $this->assertCount(2, $newUsers->all());
    }

    public function testProjectionShouldAffectCacheKey(): void
    {
        // Set
        $this->instance(CacheComponentInterface::class, new CacheComponent());
        $this->createUser('John');

        // Actions
        $users = EmbeddedUser::where(['age' => 30], [], true);
        $this->assertCount(1, $users->all());

        // Create a user after the cursor was cached.
        $this->createUser('Bob');

        // Getting users again
        $newUsers = EmbeddedUser::where(['age' => 30], ['age'], true);

        // Assert that the amount of users didn't change.
        $this->assertCount(2, $newUsers->all());
    }

    private function createUser(string $name): EmbeddedUser
    {
        $user = new EmbeddedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $user->age = 30;
        $this->assertTrue($user->save());

        return $user;
    }
}
