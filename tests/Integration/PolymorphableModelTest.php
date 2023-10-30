<?php

namespace Mongolid\Tests\Integration;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Tests\Stubs\PolymorphedReferencedUser;
use Mongolid\Tests\Stubs\ReferencedUser;

class PolymorphableModelTest extends IntegrationTestCase
{
    public function testShoudlRetrivePolymorphedModelFromFill(): void
    {
        // Set
        $polymorphedUserData = [
            'type' => 'polymorphed',
            'new_field' => true,
        ];

        // Actions
        $polymorphedUser = ReferencedUser::fill($polymorphedUserData);

        // Assertions
        $this->assertSame(PolymorphedReferencedUser::class, $polymorphedUser::class);
        $this->assertSame('polymorphed', $polymorphedUser->type);
        $this->assertTrue($polymorphedUser->new_field);
    }

    public function testShoudlRetrivePolymorphedModelFromDatabase(): void
    {
        // Set
        $polymorphedUser = ReferencedUser::fill([
            'type' => 'polymorphed',
            'new_field' => true,
        ]);
        $polymorphedUser->save();

        // Actions
        $polymorphedUserFromDatabase = ReferencedUser::first();

        // Assertions
        $this->assertSame(PolymorphedReferencedUser::class, $polymorphedUserFromDatabase::class);
        $this->assertSame('polymorphed', $polymorphedUserFromDatabase->type);
        $this->assertTrue($polymorphedUserFromDatabase->new_field);
    }

    /**
     *  Mongolid uses the 'unserialize' feature of the MongoDB extension to create a model instance from the database.
     *  However, in the current implementation, changing the model instance based on the PolymorphInterface is not possible.
     *  To ensure the expected behavior, consider creating the model with the desired type (possibly using the 'fill' method) before saving it.
     */
    public function testShouldRetrieveFromDatabaseAndNotRespectPolymorphInterface(): void
    {
        // Set
        $user = new ReferencedUser();
        $user->type = 'polymorphed';
        $user->some_date = new UTCDateTime(new DateTime('2018-10-10 00:00:00'));
        $user->save();

        // Actions
        $userFromDatabase = ReferencedUser::first();

        // Assertions
        $this->assertSame(ReferencedUser::class, $userFromDatabase::class);
        $this->assertEquals($user->some_date->toDateTime(), $userFromDatabase->some_date?->toDateTime());
        $this->assertSame($user->type, $userFromDatabase->type);
    }
}
