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

    public function testShouldRetrieveSonOfUser()
    {
        // create parent
        $chuck = $this->createUser('Chuck', '010');
        $john = $this->createUser('John', '369');
        $john->son()->attach($chuck);

        $this->assertSame(['010'], $john->son_id); // TODO store as single code (not array)
        $this->assertSon($chuck, $john);
        // hit cache
        $this->assertSon($chuck, $john);

        // replace son
        $bob = $this->createUser('Bob', '987');
        $john->son()->detach(); //todo remove this line and ensure only one son is attached
        $john->son()->attach($bob);

        $this->assertSame(['987'], $john->son_id);
        $this->assertSon($bob, $john);
        // hit cache
        $this->assertSon($bob, $john);

        // remove
        //unset($john->son_id);// TODO make this work!
        $john->son()->detach();

        $this->assertNull($john->son_id);
        $this->assertNull($john->son);
    }

    private function createUser(string $name, string $code = null): User
    {
        $user = new User();
        $user->_id = new ObjectId();
        $user->name = $name;
        if ($code) {
            $user->code = $code;
        }
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertParent($expected, User $model)
    {
        $parent = $model->parent;
        $this->assertInstanceOf(User::class, $parent);
        $this->assertEquals($expected, $parent);
    }

    private function assertSon($expected, User $model)
    {
        $son = $model->son;
        $this->assertInstanceOf(User::class, $son);
        $this->assertEquals($expected, $son);
    }
}
