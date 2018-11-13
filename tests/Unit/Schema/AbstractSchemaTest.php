<?php
namespace Mongolid\Schema;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\TestCase;
use Mongolid\Util\SequenceService;

class AbstractSchemaTest extends TestCase
{
    public function testShouldNotBeDynamicByDefault()
    {
        // Arrange
        $schema = m::mock(AbstractSchema::class.'[]');

        // Assert
        $this->assertAttributeEquals(false, 'dynamic', $schema);
    }

    public function testMustHaveAnEntityClass()
    {
        // Arrange
        $schema = m::mock(AbstractSchema::class.'[]');

        // Assert
        $this->assertAttributeEquals('stdClass', 'entityClass', $schema);
    }

    public function testShouldCastNullIntoObjectId()
    {
        // Arrange
        $schema = m::mock(AbstractSchema::class.'[]');
        $value = null;

        // Assert
        $this->assertInstanceOf(
            ObjectId::class,
            $schema->objectId($value)
        );
    }

    public function testShouldNotCastRandomStringIntoObjectId()
    {
        // Arrange
        $schema = m::mock(AbstractSchema::class.'[]');
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
        $schema = m::mock(AbstractSchema::class.'[]');
        $value = '507f1f77bcf86cd799439011';

        // Assert
        $this->assertInstanceOf(
            ObjectId::class,
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
        $schema = m::mock(AbstractSchema::class.'[]');
        $sequenceService = $this->instance(SequenceService::class, m::mock(SequenceService::class));
        $value = null;

        $schema->collection = 'resources';

        // Act
        $sequenceService->expects()
            ->getNextValue('resources')
            ->andReturn(7);

        // Assertion
        $this->assertEquals(7, $schema->sequence($value));
    }

    public function testShouldNotAutoIncrementSequenceIfValueIsNotNull()
    {
        $schema = m::mock(AbstractSchema::class.'[]');
        $sequenceService = $this->instance(SequenceService::class, m::mock(SequenceService::class));
        $value = 3;

        $schema->collection = 'resources';

        // Act
        $sequenceService->expects()
            ->getNextValue('resources')
            ->never()
            ->andReturn(7); // Should never be returned

        // Assertion
        $this->assertEquals(3, $schema->sequence($value));
    }

    public function testShouldCastDocumentTimestamps()
    {
        // Arrange
        $schema = m::mock(AbstractSchema::class.'[]');
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
        $schema = m::mock(AbstractSchema::class.'[]');
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
        $schema = m::mock(AbstractSchema::class.'[]');

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
