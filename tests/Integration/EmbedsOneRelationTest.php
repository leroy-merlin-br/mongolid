<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Tests\Integration\Stubs\EmbeddedUser;

class EmbedsOneRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveParentOfUser()
    {
        // create parent
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->parent()->add($chuck);

        $this->assertParent($chuck, $john);
        // hit cache
        $this->assertParent($chuck, $john);

        // replace parent
        $bob = $this->createUser('Bob');
        $john->parent()->remove(); //todo remove this line and ensure only one parent is added
        $john->parent()->add($bob);

        $this->assertParent($bob, $john);
        // hit cache
        $this->assertParent($bob, $john);

        // remove
        //unset($john->embedded_parent);// TODO make this work!
        $john->parent()->removeAll();

        $this->assertNull($john->embedded_parent);
        $this->assertNull($john->parent);
    }

    public function testShouldRetrieveSonOfUser()
    {
        // create parent
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->son()->add($chuck);

        $this->assertSon($chuck, $john);
        // hit cache
        $this->assertSon($chuck, $john);

        // replace son
        $bob = $this->createUser('Bob');
        $john->son()->remove(); //todo remove this line and ensure only one son is added
        $john->son()->add($bob);

        $this->assertSon($bob, $john);
        // hit cache
        $this->assertSon($bob, $john);

        // remove
        //unset($john->arbitrary_field);// TODO make this work!
        $john->son()->removeAll();

        $this->assertNull($john->arbitrary_field);
        $this->assertNull($john->son);
    }

    private function createUser(string $name): EmbeddedUser
    {
        $user = new EmbeddedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertParent($expected, EmbeddedUser $model)
    {
        $parent = $model->parent;
        $this->assertInstanceOf(EmbeddedUser::class, $parent);
        $this->assertEquals($expected, $parent);
        $this->assertSame([$expected], $model->embedded_parent); // TODO store as single array
    }

    private function assertSon($expected, EmbeddedUser $model)
    {
        $son = $model->son;
        $this->assertInstanceOf(EmbeddedUser::class, $son);
        $this->assertEquals($expected, $son);
        $this->assertSame([$expected], $model->arbitrary_field); // TODO store as single array
    }
}
