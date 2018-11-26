<?php
namespace Mongolid\Model;

use Mongolid\TestCase;

class HasAttributesTraitTest extends TestCase
{
    public function testShouldGetAttributeFromMutator()
    {
        // Set
        $model = new class()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function getShortNameDocumentAttribute()
            {
                return 'Other name';
            }
        };

        $model->setDocumentAttribute('short_name', 'My awesome name');
        $result = $model->getDocumentAttribute('short_name');

        // Assertions
        $this->assertSame('Other name', $result);
    }

    public function testShouldIgnoreMutators()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function getShortNameDocumentAttribute()
            {
                return 'Other name';
            }

            public function setShortNameDocumentAttribute($value)
            {
                return strtoupper($value);
            }
        };

        $model->setDocumentAttribute('short_name', 'My awesome name');
        $result = $model->getDocumentAttribute('short_name');

        // Assert
        $this->assertSame('My awesome name', $result);
    }

    public function testShouldSetAttributeFromMutator()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function setShortNameDocumentAttribute($value)
            {
                return strtoupper($value);
            }
        };

        $model->setDocumentAttribute('short_name', 'My awesome name');
        $result = $model->getDocumentAttribute('short_name');

        // Assert
        $this->assertSame('MY AWESOME NAME', $result);
    }

    /**
     * @dataProvider getFillableOptions
     */
    public function testShouldFillOnlyPermittedAttributes(
        $fillable,
        $guarded,
        $input,
        $expected
    ) {
        // Arrange
        $model = new class($fillable, $guarded)
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct(array $fillable, array $guarded)
            {
                $this->fillable = $fillable;
                $this->guarded = $guarded;
            }
        };

        // Act
        $model->fill($input);

        // Assert
        $this->assertSame($expected, $model->getDocumentAttributes());
    }

    public function testShouldForceFillAttributes()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;
        };

        $input = [
            'name' => 'Josh',
            'not_allowed_attribute' => true,
        ];

        // Act
        $model->fill($input, true);

        // Assert
        $this->assertTrue($model->getDocumentAttribute('not_allowed_attribute'));
    }

    public function testShouldBeCastableToArray()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;
            use HasRelationsTrait;
        };

        $model->setDocumentAttribute('name', 'John');
        $model->setDocumentAttribute('age', 25);

        // Assert
        $this->assertSame(
            ['name' => 'John', 'age' => 25],
            $model->toArray()
        );
    }

    public function testShouldSetOriginalAttributes()
    {
        // Arrange
        $model = new class() implements HasAttributesInterface
        {
            use HasAttributesTrait;
            use HasRelationsTrait;
        };

        $model->name = 'John';
        $model->age = 25;

        // Act
        $model->syncOriginalDocumentAttributes();

        // Assert
        $this->assertSame($model->getDocumentAttributes(), $model->getOriginalDocumentAttributes());
    }

    public function testShouldFallbackOriginalAttributesIfUnserializationFails()
    {
        // Arrange
        $model = new class() implements HasAttributesInterface
        {
            use HasAttributesTrait;
            use HasRelationsTrait;

            public function __construct()
            {
                $this->attributes = [function () {
                },
                ];
            }
        };

        // Act
        $model->syncOriginalDocumentAttributes();

        // Assert
        $this->assertSame($model->getDocumentAttributes(), $model->getOriginalDocumentAttributes());
    }

    public function getFillableOptions()
    {
        return [
            // -----------------------------
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

            // -----------------------------
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

            // -----------------------------
            '$fillable = []; $guarded = []' => [
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

            // -----------------------------
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

            // -----------------------------
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


    public function testShouldCheckIfAttributeIsSet()
    {
        // Set
        $model = new class() extends AbstractModel
        {
        };
        $model->fill(['name' => 'John', 'ignored' => null]);

        // Assertions
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
        $this->assertFalse(isset($model->ignored));
    }

    public function testShouldCheckIfMutatedAttributeIsSet()
    {
        // Set
        $model = new class() extends AbstractModel
        {
            /**
             * {@inheritdoc}
             */
            public $mutable = true;

            public function getNameDocumentAttribute()
            {
                return 'John';
            }
        };

        // Assertions
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
    }
}
