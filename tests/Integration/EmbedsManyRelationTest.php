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
        // hit cache
        $this->assertSiblings([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->siblings()->add($mary);

        $this->assertSiblings([$chuck, $mary], $john);
        // hit cache
        $this->assertSiblings([$chuck, $mary], $john);

        // remove one sibling
        $john->siblings()->remove($chuck);
        $this->assertSiblings([$mary], $john);
        // hit cache
        $this->assertSiblings([$mary], $john);

        // replace siblings
        $bob = $this->createUser('Bob');
        // unset($john->embedded_siblings); // TODO make this work!
        $john->siblings()->removeAll();
        $this->assertEmpty($john->siblings->all());
        $john->siblings()->add($bob);

        $this->assertSiblings([$bob], $john);
        // hit cache
        $this->assertSiblings([$bob], $john);

        // remove with unembed
        $john->siblings()->remove($bob);

        $this->assertEmpty($john->embedded_siblings);
        $this->assertEmpty($john->siblings->all());
    }

    public function testShouldRetrieveGrandsonsOfUser()
    {
        // create sibling
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->grandsons()->add($chuck);

        $this->assertSame([$chuck], $john->other_arbitrary_field);
        $this->assertGrandsons([$chuck], $john);
        // hit cache
        $this->assertGrandsons([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->grandsons()->add($mary);

        $this->assertSame([$chuck, $mary], $john->other_arbitrary_field);
        $this->assertGrandsons([$chuck, $mary], $john);
        // hit cache
        $this->assertGrandsons([$chuck, $mary], $john);

        // remove one sibling
        $john->grandsons()->remove($chuck);

        $this->assertSame([$mary], $john->other_arbitrary_field);
        $this->assertGrandsons([$mary], $john);
        // hit cache
        $this->assertGrandsons([$mary], $john);

        // replace grandsons
        $bob = $this->createUser('Bob');
        // unset($john->other_arbitrary_field); // TODO make this work!
        $john->grandsons()->removeAll();
        $this->assertEmpty($john->grandsons->all());
        $john->grandsons()->add($bob);

        $this->assertSame([$bob], $john->other_arbitrary_field);
        $this->assertGrandsons([$bob], $john);
        // hit cache
        $this->assertGrandsons([$bob], $john);

        // remove with unembed
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
    }

    private function assertGrandsons($expected, EmbeddedUser $model)
    {
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expected, $grandsons->all());
    }
}
