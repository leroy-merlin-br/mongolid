<?php

namespace Mongolid\Model;

use DateTime;
use DateTimeImmutable;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\PolymorphedReferencedUser;
use Mongolid\Tests\Stubs\ReferencedUser;

final class HasAttributesTraitTest extends TestCase
{
    public function testShouldGetAttributeFromMutator(): void
    {
        // Set
        $model = new class ()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function getShortNameDocumentAttribute(): string
            {
                return 'Other name';
            }
        };

        // Actions
        $model->setDocumentAttribute('short_name', 'My awesome name');
        $result = $model->getDocumentAttribute('short_name');

        // Assertions
        $this->assertSame('Other name', $result);
    }

    public function testShouldIgnoreMutators(): void
    {
        // Set
        $model = new class ()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function getShortNameDocumentAttribute(): string
            {
                return 'Other name';
            }

            public function setShortNameDocumentAttribute($value): string
            {
                return strtoupper($value);
            }
        };

        // Actions
        $model->setDocumentAttribute('short_name', 'My awesome name');
        $result = $model->getDocumentAttribute('short_name');

        // Assertions
        $this->assertSame('My awesome name', $result);
    }

    public function testShouldSetAttributeFromMutator(): void
    {
        // Set
        $model = new class ()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function setShortNameDocumentAttribute($value): string
            {
                return strtoupper($value);
            }
        };

        // Actions
        $model->setDocumentAttribute('short_name', 'My awesome name');
        $result = $model->getDocumentAttribute('short_name');

        // Assertions
        $this->assertSame('MY AWESOME NAME', $result);
    }

    /**
     * @dataProvider getFillableOptions
     */
    public function testShouldFillOnlyPermittedAttributes(
        array $fillable,
        array $guarded,
        array $input,
        array $expected
    ) {
        // Set
        $model = new class ($fillable, $guarded) implements HasAttributesInterface
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct(array $fillable, array $guarded)
            {
                $this->fillable = $fillable;
                $this->guarded = $guarded;
            }
        };

        // Actions
        $model = $model::fill($input, $model);

        // Assertions
        $this->assertSame($expected, $model->getDocumentAttributes());
    }

    public function testFillShouldRetrievePolymorphedModel(): void
    {
        // Set
        $input = [
            'type' => 'polymorphed',
            'new_field' => 'hello',
        ];
        // Actions
        $result = ReferencedUser::fill($input);

        // Assertions
        $this->assertInstanceOf(PolymorphedReferencedUser::class, $result);
        $this->assertSame('polymorphed', $result->type);
        $this->assertSame('hello', $result->new_field);
    }

    public function testFillShouldRetrievePolymorphedModelConsideringModelAttributes(): void
    {
        // Set
        $input = [
            'new_field' => 'hello',
        ];
        $model = new ReferencedUser();
        $model->type = 'polymorphed';

        // Actions
        $result = ReferencedUser::fill($input, $model);

        // Assertions
        $this->assertInstanceOf(PolymorphedReferencedUser::class, $result);
        $this->assertSame('polymorphed', $result->type);
        $this->assertSame('hello', $result->new_field);
    }

    public function testFillShouldRetrievePolymorphedModelConsideringModelAttributesButPrioritizingInput(): void
    {
        // Set
        $input = [
            'type' => 'default',
            'new_field' => 'hello',
        ];
        $model = new PolymorphedReferencedUser();
        $model->type = 'polymorphed';

        // Actions
        $result = ReferencedUser::fill($input, $model);

        // Assertions
        $this->assertInstanceOf(ReferencedUser::class, $result);
        $this->assertSame('default', $result->type);
        $this->assertSame('hello', $result->new_field);
    }

    public function testFillShouldRetrievePolymorphedModelEvenWithExistingModel(): void
    {
        // Set
        $input = [
            'type' => 'polymorphed',
            'new_field' => 'hello',
            'exclusive' => 'value', // should not be set
            'other_exclusive' => 'value from fill', // should not be set
        ];
        $model = new ReferencedUser();
        $id = new ObjectId();
        $model->_id = $id;
        $model->name = 'Albert';
        $model->other_exclusive = 'other value'; // should be inherited
        // Actions
        $result = ReferencedUser::fill($input, $model);

        // Assertions
        $this->assertInstanceOf(PolymorphedReferencedUser::class, $result);
        $this->assertSame('polymorphed', $result->type);
        $this->assertSame('hello', $result->new_field);
        $this->assertSame($id, $result->_id);
        $this->assertSame('Albert', $result->name);
        $this->assertNull($result->exclusive);
        $this->assertSame('other value', $result->other_exclusive);
    }

    public function testFillShouldHoldValuesOnModel(): void
    {
        // Set
        $input = [
            'type' => 'regular',
            'new_field' => 'hello', // should not be set
        ];
        $model = new ReferencedUser();
        $id = new ObjectId();
        $model->_id = $id;
        $model->name = 'Albert';
        // Actions
        $result = ReferencedUser::fill($input, $model);

        // Assertions
        $this->assertSame($model, $result);
        $this->assertSame(
            [
                '_id' => $id,
                'name' => 'Albert',
                'type' => 'regular',
            ],
            $model->getDocumentAttributes()
        );
    }

    public function testFillShouldNotHoldValuesOnModelIfPolymorphed(): void
    {
        // Set
        $input = [
            'type' => 'polymorphed',
            'new_field' => 'hello',
        ];
        $model = new ReferencedUser();
        $id = new ObjectId();
        $model->_id = $id;
        $model->name = 'Albert';
        // Actions
        $result = ReferencedUser::fill($input, $model);

        // Assertions
        $this->assertNotSame($model, $result);
        $this->assertSame(
            [
                '_id' => $id,
                'name' => 'Albert',
                'type' => 'polymorphed',
                'new_field' => 'hello',
            ],
            $result->getDocumentAttributes()
        );
        $this->assertSame(
            [
                '_id' => $id,
                'name' => 'Albert',
            ],
            $model->getDocumentAttributes()
        );
    }

    public function testShouldForceFillAttributes(): void
    {
        // Set
        $model = new class () implements HasAttributesInterface
        {
            use HasAttributesTrait;
            use HasRelationsTrait;
        };

        $input = [
            'name' => 'Josh',
            'not_allowed_attribute' => true,
        ];

        // Actions
        $model = $model::fill($input, $model, true);
        $result = $model->getDocumentAttribute('not_allowed_attribute');

        // Assertions
        $this->assertTrue($result);
    }

    public function testShouldBeCastableToArray(): void
    {
        // Set
        $model = new class ()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;
        };

        $model->setDocumentAttribute('name', 'John');
        $model->setDocumentAttribute('age', 25);

        // Actions
        $result = $model->toArray();

        // Assertions
        $this->assertSame(['name' => 'John', 'age' => 25], $result);
    }

    public function testShouldSetOriginalAttributes(): void
    {
        // Set
        $model = new class () implements HasAttributesInterface
        {
            use HasAttributesTrait;
            use HasRelationsTrait;
        };

        $model->name = 'John';
        $model->age = 25;

        // Actions
        $model->syncOriginalDocumentAttributes();
        $result = $model->getOriginalDocumentAttributes();

        // Assertions
        $this->assertSame($model->getDocumentAttributes(), $result);
    }

    public function testShouldFallbackOriginalAttributesIfUnserializationFails(): void
    {
        // Set
        $model = new class () implements HasAttributesInterface
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct()
            {
                $this->attributes = [
                    function () {
                    },
                ];
            }
        };

        // Actions
        $model->syncOriginalDocumentAttributes();
        $result = $model->getOriginalDocumentAttributes();

        // Assertions
        $this->assertSame($model->getDocumentAttributes(), $result);
    }

    public function testShouldCheckIfAttributeIsSet(): void
    {
        // Set
        $model = new class () extends AbstractModel
        {
        };

        // Actions
        $model = $model::fill(['name' => 'John', 'ignored' => null]);

        // Assertions
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
        $this->assertFalse(isset($model->ignored));
    }

    public function testShouldCheckIfMutatedAttributeIsSet(): void
    {
        // Set
        $model = new class () extends AbstractModel
        {
            public bool $mutable = true;

            public function getNameDocumentAttribute(): string
            {
                return 'John';
            }
        };

        // Assertions
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
    }

    public function testShouldCastAttributeToDateTimeWhenLoadingFromDatabase(): void
    {
        // Set
        $model = new class () extends AbstractModel
        {
            protected array $casts = [
                'expires_at' => 'datetime',
                'birthdate' => 'immutable_datetime',
            ];
        };
        $model->expires_at = new UTCDateTime(
            DateTime::createFromFormat('d/m/Y H:i:s', '08/10/2025 12:30:45')
        );
        $model->birthdate = new UTCDateTime(
            DateTimeImmutable::createFromFormat('d/m/Y', '02/04/1990')
        );

        // Assertions
        $this->assertInstanceOf(DateTime::class, $model->expires_at);
        $this->assertSame(
            '08/10/2025 12:30:45',
            $model->expires_at->format('d/m/Y H:i:s')
        );

        $this->assertInstanceOf(DateTimeImmutable::class, $model->birthdate);
        $this->assertSame('02/04/1990', $model->birthdate->format('d/m/Y'));
    }

    public function testShouldCastAttributeToUTCDateTimeWhenSettingAttributes(): void
    {
        // Set
        $model = new class () extends AbstractModel
        {
            protected array $casts = [
                'expires_at' => 'datetime',
                'birthdate' => 'immutable_datetime',
            ];
        };

        // Actions
        $model->expires_at = DateTime::createFromFormat(
            'd/m/Y H:i:s',
            '08/10/2025 12:30:45'
        );

        // Assertions
        $this->assertInstanceOf(
            UTCDateTime::class,
            $model->getDocumentAttributes()['expires_at']
        );
        $this->assertInstanceOf(DateTime::class, $model->expires_at);
    }

    public function getFillableOptions(): array
    {
        return [
            '$fillable = []; $guarded = []' => [
                'fillable' => [],
                'guarded' => [],
                'input' => [
                    'name' => 'John',
                    'age' => 25,
                    'sex' => 'male',
                ],
                'expected' => [
                    'name' => 'John',
                    'age' => 25,
                    'sex' => 'male',
                ],
            ],
            '$fillable = ["name"]; $guarded = []' => [
                'fillable' => ['name'],
                'guarded' => [],
                'input' => [
                    'name' => 'John',
                    'age' => 25,
                    'sex' => 'male',
                ],
                'expected' => [
                    'name' => 'John',
                ],
            ],
            '$fillable = []; $guarded = [sex]' => [
                'fillable' => [],
                'guarded' => ['sex'],
                'input' => [
                    'name' => 'John',
                    'age' => 25,
                    'sex' => 'male',
                ],
                'expected' => [
                    'name' => 'John',
                    'age' => 25,
                ],
            ],
            '$fillable = ["name", "sex"]; $guarded = ["sex"]' => [
                'fillable' => ['name', 'sex'],
                'guarded' => ['sex'],
                'input' => [
                    'name' => 'John',
                    'age' => 25,
                    'sex' => 'male',
                ],
                'expected' => [
                    'name' => 'John',
                ],
            ],
            'ignore nulls but not falsy ones' => [
                'fillable' => ['name', 'surname', 'sex', 'age', 'has_sex'],
                'guarded' => [],
                'input' => [
                    'name' => 'John',
                    'surname' => '',
                    'sex' => null,
                    'age' => 0,
                    'has_sex' => false,
                ],
                'expected' => [
                    'name' => 'John',
                    'surname' => '',
                    'age' => 0,
                    'has_sex' => false,
                ],
            ],
        ];
    }
}
