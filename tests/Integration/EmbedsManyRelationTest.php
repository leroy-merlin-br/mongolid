<?php
namespace Mongolid\Tests\Integration;

use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Tests\Integration\Stubs\EmbeddedUser;

class EmbedsManyRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveSiblingsOfUser()
    {
        // create sibling
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->siblings()->add($chuck);

        $this->assertSiblings([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->siblings()->add($mary);

        $this->assertSiblings([$chuck, $mary], $john);

        // remove one sibling
        $john->siblings()->remove($chuck);
        $this->assertSiblings([$mary], $john);

        // replace siblings
        $john->siblings()->remove($mary);
        $bob = $this->createUser('Bob');

        // unset
        $john->siblings()->add($bob);
        $this->assertSiblings([$bob], $john);
        unset($john->embedded_siblings);
        $this->assertEmpty($john->siblings->all());
        $this->assertEmpty($john->embedded_siblings);

        // remove all
        $john->siblings()->add($bob);
        $this->assertSiblings([$bob], $john);
        $john->siblings()->removeAll();
        $this->assertEmpty($john->siblings->all());
        $this->assertEmpty($john->embedded_siblings);

        // remove
        $john->siblings()->add($bob);
        $this->assertSiblings([$bob], $john);
        $john->siblings()->remove($bob);
        $this->assertEmpty($john->embedded_siblings);
        $this->assertEmpty($john->siblings->all());
    }

    public function testShouldRetrieveGrandsonsOfUserUsingCustomKey()
    {
        // create grandson
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->grandsons()->add($chuck);

        $this->assertSame([$chuck], $john->other_arbitrary_field);
        $this->assertGrandsons([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->grandsons()->add($mary);

        $this->assertSame([$chuck, $mary], $john->other_arbitrary_field);
        $this->assertGrandsons([$chuck, $mary], $john);

        // remove one grandson
        $john->grandsons()->remove($chuck);

        $this->assertSame([$mary], $john->other_arbitrary_field);
        $this->assertGrandsons([$mary], $john);

        // replace grandsons
        $john->grandsons()->remove($mary);
        $bob = $this->createUser('Bob');

        // unset
        $john->grandsons()->add($bob);
        $this->assertGrandsons([$bob], $john);
        unset($john->other_arbitrary_field);
        $this->assertEmpty($john->other_arbitrary_field);
        $this->assertEmpty($john->grandsons->all());

        // removeAll
        $john->grandsons()->add($bob);
        $this->assertGrandsons([$bob], $john);
        $john->grandsons()->removeAll();
        $this->assertEmpty($john->other_arbitrary_field);
        $this->assertEmpty($john->grandsons->all());

        // remove
        $john->grandsons()->add($bob);
        $this->assertGrandsons([$bob], $john);
        $john->grandsons()->remove($bob);
        $this->assertEmpty($john->other_arbitrary_field);
        $this->assertEmpty($john->grandsons->all());
    }

    private function createUser(string $name): EmbeddedUser
    {
        $user = new EmbeddedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertSiblings($expected, EmbeddedUser $model)
    {
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());
        $this->assertSame($expected, $model->embedded_siblings);

        // hit cache
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());
        $this->assertSame($expected, $model->embedded_siblings);
    }

    private function assertGrandsons($expected, EmbeddedUser $model)
    {
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expected, $grandsons->all());

        // hit cache
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expected, $grandsons->all());
    }
}
