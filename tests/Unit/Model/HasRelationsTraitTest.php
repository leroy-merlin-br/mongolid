<?php

namespace Mongolid\Model;

use Mockery as m;
use MongoDB\BSON\ObjectId;
use Mongolid\Cursor\EmbeddedCursor;
use Mongolid\Query\Builder;
use Mongolid\TestCase;
use Mongolid\Tests\Stubs\EmbeddedUser;
use Mongolid\Tests\Stubs\ReferencedUser;

final class HasRelationsTraitTest extends TestCase
{
    /**
     * @dataProvider referencesOneScenarios
     */
    public function testShouldReferenceOne($fieldValue, array $expectedQuery): void
    {
        // Set
        $model = new ReferencedUser();
        $model->parent_id = $fieldValue;

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)->makePartial()
        );
        $expected = new ReferencedUser();

        // Expectations
        $builder
            ->expects('first')
            ->with(m::type(ReferencedUser::class), $expectedQuery, [], false)
            ->andReturn($expected);

        // Actions
        $result = $model->parent;

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldNotPerformQueryForNullReference(): void
    {
        // Set
        $model = new ReferencedUser();

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)->makePartial()
        );

        // Expectations
        $builder
            ->expects('first')
            ->withAnyArgs()
            ->never();

        // Actions
        $result = $model->parent;

        // Assertions
        $this->assertNull($result);
    }

    /**
     * @dataProvider referencesManyScenarios
     */
    public function testShouldReferenceMany($fieldValue, array $expectedQuery): void
    {
        // Set
        $model = new ReferencedUser();
        $model->siblings_ids = $fieldValue;

        $builder = $this->instance(
            Builder::class,
            m::mock(Builder::class)->makePartial()
        );
        $expected = new EmbeddedCursor([]);

        // Expectations
        $builder
            ->expects('where')
            ->with(m::type(ReferencedUser::class), $expectedQuery, [], false)
            ->andReturn($expected);

        // Actions
        $result = $model->siblings;

        // Assertions
        $this->assertSame($expected, $result);
    }

    public function testShouldEmbedOne(): void
    {
        // Set
        $model = new EmbeddedUser();

        $embeddedModel = new EmbeddedUser();
        $embeddedModel->_id = 12345;
        $embeddedModel->name = 'John';
        $embeddedModel->syncOriginalDocumentAttributes();

        $model->embedded_parent = $embeddedModel;

        // Actions
        $result = $model->parent;

        // Assertions
        $this->assertInstanceOf(EmbeddedUser::class, $result);
        $this->assertSame($embeddedModel, $result);
    }

    public function testEmbedOneShouldAllowOnlyOneEmbeddedModel(): void
    {
        // Set
        $model = new EmbeddedUser();

        $oldEmbeddedModel = new EmbeddedUser();
        $oldEmbeddedModel->_id = 12345;
        $oldEmbeddedModel->name = 'John';
        $oldEmbeddedModel->syncOriginalDocumentAttributes();

        $newEmbeddedModel = new EmbeddedUser();
        $newEmbeddedModel->_id = 54321;
        $newEmbeddedModel->name = 'Bob';
        $newEmbeddedModel->syncOriginalDocumentAttributes();

        $model->embedded_parent = $oldEmbeddedModel;

        // Actions
        $model->parent()->add($newEmbeddedModel);
        $result = $model->parent;

        // Assertions
        $this->assertInstanceOf(EmbeddedUser::class, $result);
        $this->assertSame($newEmbeddedModel, $result);
    }

    /**
     * @dataProvider embedsManyScenarios
     */
    public function testShouldEmbedMany($fieldValue, array $expectedItems): void
    {
        // Set
        $model = new EmbeddedUser();
        $model->embedded_siblings = $fieldValue;

        // Actions
        $result = $model->siblings;

        // Assertions
        $this->assertInstanceOf(EmbeddedCursor::class, $result);
        $this->assertContainsOnlyInstancesOf(
            EmbeddedUser::class,
            $result->all()
        );
        $this->assertSame($expectedItems, $result->all());
    }

    public function referencesOneScenarios(): array
    {
        return [
            'referenced by string id' => [
                'fieldValue' => 'abc123',
                'expectedQuery' => ['_id' => 'abc123'],
            ],
            'referenced by objectId represented as string' => [
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'expectedQuery' => [
                    '_id' => new ObjectId(
                        '577afb0b4d3cec136058fa82'
                    ),
                ],
            ],
            'referenced by an objectId itself' => [
                'fieldValue' => new ObjectId('577afb0b4d3cec136058fa82'),
                'expectedQuery' => [
                    '_id' => new ObjectId(
                        '577afb0b4d3cec136058fa82'
                    ),
                ],
            ],
        ];
    }

    public function referencesManyScenarios(): array
    {
        return [
            'referenced by string id' => [
                'fieldValue' => 'abc123',
                'expectedQuery' => ['_id' => ['$in' => ['abc123']]],
            ],
            'referenced by objectId represented as string' => [
                'fieldValue' => '577afb0b4d3cec136058fa82',
                'expectedQuery' => [
                    '_id' => [
                        '$in' => [new ObjectId(
                            '577afb0b4d3cec136058fa82'
                        ),
                        ],
                    ],
                ],
            ],
            'referenced by an objectId itself' => [
                'fieldValue' => new ObjectId('577afb0b4d3cec136058fa82'),
                'expectedQuery' => [
                    '_id' => [
                        '$in' => [new ObjectId(
                            '577afb0b4d3cec136058fa82'
                        ),
                        ],
                    ],
                ],
            ],
            'series of objectIds' => [
                'fieldValue' => [new ObjectId(
                    '577afb0b4d3cec136058fa82'
                ), new ObjectId(
                    '577afb7e4d3cec136258fa83'
                ),
                ],
                'expectedQuery' => [
                    '_id' => [
                        '$in' => [
                            new ObjectId('577afb0b4d3cec136058fa82'),
                            new ObjectId('577afb7e4d3cec136258fa83'),
                        ],
                    ],
                ],
            ],
            'series of objectIds as strings' => [
                'fieldValue' => ['577afb0b4d3cec136058fa82', '577afb7e4d3cec136258fa83'],
                'expectedQuery' => [
                    '_id' => [
                        '$in' => [
                            new ObjectId('577afb0b4d3cec136058fa82'),
                            new ObjectId('577afb7e4d3cec136258fa83'),
                        ],
                    ],
                ],
            ],
            'Model referenced with null' => [
                'fieldValue' => null,
                'expectedQuery' => ['_id' => ['$in' => []]],
            ],
        ];
    }

    public function embedsManyScenarios(): array
    {
        $model1 = new EmbeddedUser();
        $model1->_id = 12345;
        $model1->name = 'John';
        $model1->syncOriginalDocumentAttributes();

        $model2 = new EmbeddedUser();
        $model2->_id = 67890;
        $model2->name = 'Bob';
        $model2->syncOriginalDocumentAttributes();

        return [
            'A single embedded document' => [
                'fieldValue' => $model1,
                'expectedItems' => [$model1],
            ],
            'Many embedded documents' => [
                'fieldValue' => [$model1, $model2],
                'expectedItems' => [$model1, $model2],
            ],
        ];
    }
}
