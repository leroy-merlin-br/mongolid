<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Tests\Integration\Stubs\User;

class ReferencesManyRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveSiblingsOfUser()
    {
        // create sibling
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->siblings()->attach($chuck);

        $this->assertSiblings([$chuck], $john);
        // hit cache
        $this->assertSiblings([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->siblings()->attach($mary);

        $this->assertSiblings([$chuck, $mary], $john);
        // hit cache
        $this->assertSiblings([$chuck, $mary], $john);

        // remove one sibling
        $john->siblings()->detach($chuck);
        $this->assertSiblings([$mary], $john);
        // hit cache
        $this->assertSiblings([$mary], $john);

        // replace siblings
        $bob = $this->createUser('Bob');
        // unset($john->siblings_ids); // TODO make this work!
        $john->siblings()->detachAll();
        $this->assertEmpty($john->siblings->all());
        $john->siblings()->attach($bob);

        $this->assertSiblings([$bob], $john);
        // hit cache
        $this->assertSiblings([$bob], $john);

        // remove with unembed
        $john->siblings()->detach($bob);

        $this->assertEmpty($john->siblings->all());
    }

    private function createUser(string $name): User
    {
        $user = new User();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertSiblings($expected, User $model)
    {
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());
    }
}
