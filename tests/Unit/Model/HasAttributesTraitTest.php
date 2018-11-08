<?php
namespace Mongolid\Model;

use Mongolid\TestCase;
use stdClass;

class HasAttributesTraitTest extends TestCase
{
    public function testShouldHaveDynamicSetters()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;
        };

        $childObj = new stdClass();

        // Assert
        $model->name = 'John';
        $model->age = 25;
        $model->child = $childObj;
        $this->assertSame(
            [
                'name' => 'John',
                'age' => 25,
                'child' => $childObj,
            ],
            $model->getDocumentAttributes()
        );
    }

    public function testShouldHaveDynamicGetters()
    {
        // Arrange
        $child = new stdClass();
        $attributes = [
            'name' => 'John',
            'age' => 25,
            'child' => $child,
        ];

        $model = new class($attributes)
        {
            use HasAttributesTrait;

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }
        };

        // Assert
        $this->assertEquals('John', $model->name);
        $this->assertEquals(25, $model->age);
        $this->assertEquals($child, $model->child);
        $this->assertEquals(null, $model->nonexistant);
    }

    public function testShouldCheckIfAttributeIsSet()
    {
        // Arrange
        $model = new class(['name' => 'John', 'ignored' => null])
        {
            use HasAttributesTrait;

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }
        };

        // Assert
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
        $this->assertFalse(isset($model->ignored));
    }

    public function testShouldCheckIfMutatedAttributeIsSet()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function getNameDocumentAttribute()
            {
                return 'John';
            }
        };

        // Assert
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
    }

    public function testShouldUnsetAttributes()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;

            public function __construct()
            {
                $this->attributes = [
                    'name' => 'John',
                    'age' => 25,
                ];
            }
        };

        // Act
        unset($model->age);
        $result = $model->getDocumentAttributes();

        // Assert
        $this->assertSame(['name' => 'John'], $result);
    }

    public function testShouldGetAttributeFromMutator()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function getShortNameDocumentAttribute()
            {
                return 'Other name';
            }
        };

        $model->short_name = 'My awesome name';

        // Assert
        $this->assertEquals('Other name', $model->short_name);
        $this->assertEquals('Other name', $model->getDocumentAttribute('short_name'));
    }

    public function testShouldIgnoreMutators()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;

            public function getShortNameDocumentAttribute()
            {
                return 'Other name';
            }

            public function setShortNameDocumentAttribute($value)
            {
                return strtoupper($value);
            }
        };

        $model->short_name = 'My awesome name';

        // Assert
        $this->assertEquals('My awesome name', $model->short_name);
        $this->assertEquals('My awesome name', $model->getDocumentAttribute('short_name'));
    }

    public function testShouldSetAttributeFromMutator()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;

            public function __construct()
            {
                $this->mutable = true;
            }

            public function setShortNameDocumentAttribute($value)
            {
                return strtoupper($value);
            }
        };

        $model->short_name = 'My awesome name';

        // Assert
        $this->assertSame('MY AWESOME NAME', $model->short_name);
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
        };

        $input = [
            'name' => 'Josh',
            'not_allowed_attribute' => true,
        ];

        // Act
        $model->fill($input, true);

        // Assert
        $this->assertTrue($model->not_allowed_attribute);
    }

    public function testShouldBeCastableToArray()
    {
        // Arrange
        $model = new class()
        {
            use HasAttributesTrait;
        };

        $model->name = 'John';
        $model->age = 25;

        // Assert
        $this->assertEquals(
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
        ];
    }
}
