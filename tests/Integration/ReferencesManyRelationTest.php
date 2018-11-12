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

    public function testShouldRetrieveGrandsonsOfUser()
    {
        // create sibling
        $chuck = $this->createUser('Chuck', '010');
        $john = $this->createUser('John', '369');
        $john->grandsons()->attach($chuck);

        $this->assertSame(['010'], $john->grandsons_ids);
        $this->assertGrandsons([$chuck], $john);
        // hit cache
        $this->assertGrandsons([$chuck], $john);

        $mary = $this->createUser('Mary', '222');
        $john->grandsons()->attach($mary);

        $this->assertSame(['010', '222'], $john->grandsons_ids);
        $this->assertGrandsons([$chuck, $mary], $john);
        // hit cache
        $this->assertGrandsons([$chuck, $mary], $john);

        // remove one sibling
        $john->grandsons()->detach($chuck);

        $this->assertSame(['222'], $john->grandsons_ids);
        $this->assertGrandsons([$mary], $john);
        // hit cache
        $this->assertGrandsons([$mary], $john);

        // replace grandsons
        $bob = $this->createUser('Bob', '987');
        // unset($john->grandsons_ids); // TODO make this work!
        $john->grandsons()->detachAll();
        $this->assertEmpty($john->grandsons->all());
        $john->grandsons()->attach($bob);

        $this->assertSame(['987'], $john->grandsons_ids);
        $this->assertGrandsons([$bob], $john);
        // hit cache
        $this->assertGrandsons([$bob], $john);

        // remove with unembed
        $john->grandsons()->detach($bob);

        $this->assertEmpty($john->grandsons_ids);
        $this->assertEmpty($john->grandsons->all());
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

    private function assertSiblings($expected, User $model)
    {
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expected, $siblings->all());
    }

    private function assertGrandsons($expected, User $model)
    {
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expected, $grandsons->all());
    }
}
