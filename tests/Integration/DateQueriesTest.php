<?php

namespace Mongolid\Tests\Integration;

use DateTime;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Tests\Stubs\ReferencedUser;

final class DateQueriesTest extends IntegrationTestCase
{
    public function testShouldRetrieveDocumentsUsingDateFilters(): void
    {
        // Set
        $user = new ReferencedUser();
        $user->some_date = new UTCDateTime(
            new DateTime('2018-10-10 00:00:00')
        );

        $this->assertTrue($user->save());

        $greaterEqualResult = ReferencedUser::where(
            [
                'some_date' => [
                    '$gte' => new UTCDateTime(
                        new DateTime('2018-10-10 00:00:00')
                    ),
                ],
            ]
        );

        $this->assertCount(1, $greaterEqualResult);
        $this->assertEquals($user, $greaterEqualResult->first());

        $greaterResult = ReferencedUser::where(
            [
                'some_date' => [
                    '$gt' => new UTCDateTime(
                        new DateTime('2018-10-10 00:00:00')
                    ),
                ],
            ]
        );

        $this->assertCount(0, $greaterResult);

        $emptyResult = ReferencedUser::where(
            [
                'some_date' => [
                    '$gte' => new UTCDateTime(
                        new DateTime('2018-10-10 00:00:01')
                    ),
                ],
            ]
        );

        $this->assertCount(0, $emptyResult);
    }

    public function testShouldRetrieveDocumentsUsingDateFiltersWithRelativeDates(): void
    {
        // Set
        $user = new ReferencedUser();
        $user->some_date = new UTCDateTime(new DateTime('+10 days'));

        $this->assertTrue($user->save());

        $greaterEqualResult = ReferencedUser::where(
            [
                'some_date' => [
                    '$gte' => new UTCDateTime(),
                ],
            ]
        );

        $this->assertCount(1, $greaterEqualResult);
        $this->assertEquals($user, $greaterEqualResult->first());

        $greaterResult = ReferencedUser::where(
            [
                'some_date' => [
                    '$gt' => new UTCDateTime(),
                ],
            ]
        );

        $this->assertCount(1, $greaterResult);
        $this->assertEquals($user, $greaterResult->first());

        $emptyResult = ReferencedUser::where(
            [
                'some_date' => [
                    '$gte' => new UTCDateTime(
                        new DateTime('+10 days +1 second')
                    ),
                ],
            ]
        );

        $this->assertCount(0, $emptyResult);
    }
}
