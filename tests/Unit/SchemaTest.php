<?php

namespace Mongolid;

use Mockery as m;
use MongoDB\BSON\ObjectID;
use MongoDB\BSON\UTCDateTime;
use Mongolid\Container\Container;
use Mongolid\Schema\Schema;
use Mongolid\Util\SequenceService;

class SchemaTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldNotBeDynamicByDefault()
    {
        // Arrange
        $schema = m::mock(Schema::class.'[]');

        // Assert
        $this->assertEquals(false,  $schema->dynamic);
    }

    public function testMustHaveAnEntityClass()
    {
        // Arrange
        $schema = m::mock(Schema::class.'[]');

        // Assert
        $this->assertEquals('stdClass', $schema->entityClass);
    }

    public function testShouldCastNullIntoObjectId()
    {
        // Arrange
        $schema = new class extends Schema {
        };
        $value = null;

        // Assert
        $this->assertInstanceOf(
            ObjectID::class,
            $schema->objectId($value)
        );
    }

    public function testShouldNotCastRandomStringIntoObjectId()
    {
        // Arrange
        $schema = new class extends Schema {
        };
        $value = 'A random string';

        // Assert
        $this->assertEquals(
            $value,
            $schema->objectId($value)
        );
    }

    public function testShouldCastObjectIdStringIntoObjectId()
    {
        // Arrange
        $schema = new class extends Schema {
        };
        $value = '507f1f77bcf86cd799439011';

        // Assert
        $this->assertInstanceOf(
            ObjectID::class,
            $schema->objectId($value)
        );

        $this->assertEquals(
            $value,
            (string) $schema->objectId($value)
        );
    }

    public function testShouldCastNullIntoAutoIncrementSequence()
    {
        // Arrange
        $schema = new class extends Schema {
        };
        $sequenceService = m::mock(SequenceService::class);
        $value = null;

        $schema->collection = 'resources';

        // Act
        Container::instance(SequenceService::class, $sequenceService);

        $sequenceService->shouldReceive('getNextValue')
            ->with('resources')
            ->once()
            ->andReturn(7);

        // Assertion
        $this->assertEquals(7, $schema->sequence($value));
    }

    public function testShouldNotAutoIncrementSequenceIfValueIsNotNull()
    {
        $schema = new class extends Schema {
        };
        $sequenceService = m::mock(SequenceService::class);
        $value = 3;

        $schema->collection = 'resources';

        // Act
        Container::instance(SequenceService::class, $sequenceService);

        $sequenceService->shouldReceive('getNextValue')
            ->with('resources')
            ->never()
            ->andReturn(7); // Should never be returned

        // Assertion
        $this->assertEquals(3, $schema->sequence($value));
    }

    public function testShouldCastDocumentTimestamps()
    {
        // Arrange
        $schema = new class extends Schema {
        };
        $value = null;

        // Assertion
        $this->assertInstanceOf(
            UTCDateTime::class,
            $schema->createdAtTimestamp($value)
        );
    }

    public function testShouldRefreshUpdatedAtTimestamps()
    {
        // Arrange
        $schema = new class extends Schema {
        };
        $value = (new UTCDateTime(25));

        // Assertion
        $result = $schema->updatedAtTimestamp($value);
        $this->assertInstanceOf(UTCDateTime::class, $result);
        $this->assertNotEquals(25000, (string) $result);
    }

    /**
     * @dataProvider createdAtTimestampsFixture
     */
    public function testShouldNotRefreshCreatedAtTimestamps(
        $value,
        $expectation,
        $compareTimestamp = true
    ) {
        // Arrange
        $schema = new class extends Schema {
        };

        // Assertion
        $result = $schema->createdAtTimestamp($value);
        $this->assertInstanceOf(get_class($expectation), $result);
        if ($compareTimestamp) {
            $this->assertEquals((string) $expectation, (string) $result);
        }
    }

    public function createdAtTimestampsFixture()
    {
        return [
            'MongoDB driver UTCDateTime' => [
                'value' => new UTCDateTime(25),
                'expectation' => new UTCDateTime(25),
            ],
            'Empty field' => [
                'value' => null,
                'expectation' => new UTCDateTime(),
                'compareTimestamp' => false,
            ],
            'An string' => [
                'value' => 'foobar',
                'expectation' => new UTCDateTime(),
                'compareTimestamp' => false,
            ],
        ];
    }
}
