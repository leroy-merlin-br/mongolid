<?php

namespace Mongolid\Model;

use Mockery as m;
use stdClass;
use TestCase;

class AttributesTest extends TestCase
{
    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldHaveDynamicSetters()
    {
        // Arrange
        $model = new class() {
            use Attributes;
        };

        $childObj = new stdClass();

        // Assert
        $model->name = 'John';
        $model->age = 25;
        $model->child = $childObj;
        $this->assertAttributeEquals(
            [
                'name' => 'John',
                'age' => 25,
                'child' => $childObj,
            ],
            'attributes',
            $model
        );
    }

    public function testShouldHaveDynamicGetters()
    {
        // Arrange
        $model = new class() {
            use Attributes;
        };

        $childObj = new stdClass();
        $this->setProtected(
            $model,
            'attributes',
            [
                'name' => 'John',
                'age' => 25,
                'child' => $childObj,
            ]
        );

        // Assert
        $this->assertEquals('John', $model->name);
        $this->assertEquals(25, $model->age);
        $this->assertEquals($childObj, $model->child);
        $this->assertEquals(null, $model->nonexistant);
    }

    public function testShouldCheckIfAttributeIsSet()
    {
        // Arrange
        $model = new class() {
            use Attributes;
        };

        $this->setProtected(
            $model,
            'attributes',
            ['name' => 'John']
        );

        // Assert
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
    }

    public function testShouldCheckIfMutatedAttributeIsSet()
    {
        // Arrange
        $model = new class() {
            use Attributes;

            public function getNameAttribute()
            {
                return 'John';
            }
        };

        /* Enable mutator methods */
        $model->mutable = true;

        // Assert
        $this->assertTrue(isset($model->name));
        $this->assertFalse(isset($model->nonexistant));
    }

    public function testShouldUnsetAttributes()
    {
        // Arrange
        $model = new class() {
            use Attributes;
        };

        $this->setProtected(
            $model,
            'attributes',
            [
                'name' => 'John',
                'age' => 25,
            ]
        );

        // Assert
        unset($model->age);
        $this->assertAttributeEquals(
            [
                'name' => 'John',
            ],
            'attributes',
            $model
        );
    }

    public function testShouldGetAttributeFromMutator()
    {
        // Arrange
        $model = new class() {
            use Attributes;

            public function getSomeAttribute()
            {
                return 'something-else';
            }
        };

        /* Enable mutator methods */
        $model->mutable = true;
        $model->some = 'some-value';

        // Assert
        $this->assertEquals('something-else', $model->some);
    }

    public function testShouldIgnoreMutators()
    {
        // Arrange
        $model = new class() {
            use Attributes;

            public function getSomeAttribute()
            {
                return 'something-else';
            }

            public function setSomeAttribute($value)
            {
                return strtoupper($value);
            }
        };

        /* Disable mutator methods */
        $model->mutable = false;
        $model->some = 'some-value';

        // Assert
        $this->assertEquals('some-value', $model->some);
    }

    public function testShouldSetAttributeFromMutator()
    {
        // Arrange
        $model = new class() {
            use Attributes;

            public function setSomeAttribute($value)
            {
                return strtoupper($value);
            }
        };

        /* Enable mutator methods */
        $model->mutable = true;
        $model->some = 'some-value';

        // Assert
        $this->assertEquals('SOME-VALUE', $model->some);
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
        $model = new class() {
            use Attributes;
        };

        $this->setProtected($model, 'fillable', $fillable);
        $this->setProtected($model, 'guarded', $guarded);

        // Assert
        $model->fill($input);
        $this->assertAttributeEquals($expected, 'attributes', $model);
    }

    public function testShouldForceFillAttributes()
    {
        // Arrange
        $model = new class() {
            use Attributes;
        };

        $input = [
            'name' => 'Josh',
            'notAllowedAttribute' => true,
        ];

        // Act
        $model->fill($input, true);

        // Assert
        $this->assertTrue($model->notAllowedAttribute);
    }

    public function testShouldBeCastableToArray()
    {
        // Arrange
        $model = new class() {
            use Attributes;
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
        $model = new class() implements AttributesAccessInterface {
            use Attributes;
        };

        $model->name = 'John';
        $model->age = 25;

        // Act
        $model->syncOriginalAttributes();

        // Assert
        $this->assertAttributeEquals($model->attributes, 'original', $model);
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
