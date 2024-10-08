<?php

namespace Mongolid\Tests\Integration;

use Illuminate\Support\Arr;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Cursor\CursorInterface;
use Mongolid\Tests\Stubs\EmbeddedUser;

final class EmbedsManyRelationTest extends IntegrationTestCase
{
    public function testShouldRetrieveSiblingsOfUser(): void
    {
        // create sibling
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->siblings()->add($chuck);

        $this->assertSiblings([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->siblings()->addMany([$mary]);

        $this->assertSiblings([$chuck, $mary], $john);

        // remove one sibling
        $john->siblings()->remove($chuck);
        $this->assertSiblings([$mary], $john);

        // replace siblings
        $bob = $this->createUser('Bob');

        // unset
        $john->siblings()->replace([$bob]);
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

        // changing the field directly
        $john->siblings()->add($bob);
        $this->assertSiblings([$bob], $john);
        $john->embedded_siblings = [$chuck];
        $this->assertSiblings([$chuck], $john);

        $john->siblings()->removeAll();

        // changing the field with fillable
        $john->siblings()->add($bob);
        $this->assertSiblings([$bob], $john);
        $john = EmbeddedUser::fill(
            ['embedded_siblings' => [$chuck]],
            $john,
            true
        );
        $this->assertSiblings([$chuck], $john);
    }

    public function testShouldRetrieveGrandsonsOfUserUsingCustomKey(): void
    {
        // create grandson
        $chuck = $this->createUser('Chuck');
        $john = $this->createUser('John');
        $john->grandsons()->add($chuck);

        $this->assertGrandsons([$chuck], $john);

        $mary = $this->createUser('Mary');
        $john->grandsons()->add($mary);

        $this->assertGrandsons([$chuck, $mary], $john);

        // remove one grandson
        $john->grandsons()->remove($chuck);
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

        // changing the field directly
        $john->grandsons()->add($bob);
        $this->assertGrandsons([$bob], $john);
        $john->other_arbitrary_field = [$chuck];
        $this->assertGrandsons([$chuck], $john);

        $john->grandsons()->removeAll();

        // changing the field with fillable
        $john->grandsons()->add($bob);
        $this->assertGrandsons([$bob], $john);
        $john = EmbeddedUser::fill(
            ['other_arbitrary_field' => [$chuck]],
            $john,
            true
        );
        $this->assertGrandsons([$chuck], $john);

        // save and retrieve object
        $this->assertTrue($john->save());
        $john = $john->first($john->_id);

        $this->assertInstanceOf(
            EmbeddedUser::class,
            $john->grandsons->first()
        );
        $this->assertEquals(
            Arr::except($chuck->toArray(), 'updated_at'),
            Arr::except($john->grandsons->first()->toArray(), 'updated_at')
        );

        $chuck = $john->grandsons->first();
        $chuck->name = 'Chuck Norris';
        $john->other_arbitrary_field = [$chuck];

        $this->assertTrue($john->update());
        $john = $john->first($john->_id);

        $this->assertInstanceOf(
            EmbeddedUser::class,
            $john->grandsons->first()
        );
        $this->assertEquals(
            Arr::except($chuck->toArray(), 'updated_at'),
            Arr::except($john->grandsons->first()->toArray(), 'updated_at')
        );
    }

    private function createUser(string $name): EmbeddedUser
    {
        $user = new EmbeddedUser();
        $user->_id = new ObjectId();
        $user->name = $name;
        $this->assertTrue($user->save());

        return $user;
    }

    private function assertSiblings(array $expectedSiblings, EmbeddedUser $model): void
    {
        $expected = [];
        foreach ($expectedSiblings as $sibling) {
            $expected[] = $sibling;
            $this->assertInstanceOf(UTCDateTime::class, $sibling->created_at);
        }

        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expectedSiblings, $siblings->all());
        $this->assertSame($expected, $model->embedded_siblings);

        // hit cache
        $siblings = $model->siblings;
        $this->assertInstanceOf(CursorInterface::class, $siblings);
        $this->assertEquals($expectedSiblings, $siblings->all());
        $this->assertSame($expected, $model->embedded_siblings);
    }

    private function assertGrandsons(array $expectedGrandsons, EmbeddedUser $model): void
    {
        $expected = [];
        foreach ($expectedGrandsons as $grandson) {
            $expected[] = $grandson;
            $this->assertInstanceOf(UTCDateTime::class, $grandson->created_at);
        }

        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expectedGrandsons, $grandsons->all());
        $this->assertSame($expected, $model->other_arbitrary_field);

        // hit cache
        $grandsons = $model->grandsons;
        $this->assertInstanceOf(CursorInterface::class, $grandsons);
        $this->assertEquals($expectedGrandsons, $grandsons->all());
        $this->assertSame($expected, $model->other_arbitrary_field);
    }
}
