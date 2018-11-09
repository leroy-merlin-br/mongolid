<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Integration\Stubs\User;

class ReferencesOneRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveParentOfUser()
    {
        // create parent
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->parent()->attach($chuck);

        $this->assertParent($chuck, $john);
        // hit cache
        $this->assertParent($chuck, $john);

        // replace parent
        $bob = $this->createUser('Bob');
        $john->parent()->detach(); //todo remove this line and ensure only one parent is attached
        $john->parent()->attach($bob);

        $this->assertParent($bob, $john);
        // hit cache
        $this->assertParent($bob, $john);

        // remove
        //unset($john->parent_id);// TODO make this work!
        $john->parent()->detach();

        $this->assertNull($john->parent);
    }

    private function createUser(string $name): User
    {
        $user = new User();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertParent($expected, User $model)
    {
        $parent = $model->parent;
        $this->assertInstanceOf(User::class, $parent);
        $this->assertEquals($expected, $parent);
    }
}
