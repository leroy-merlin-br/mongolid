<?php
namespace Mongolid\Mongolid;

use Mockery as m;
use TestCase;
use Mongolid\Mongolid\Container\Ioc;

class ModelTest extends TestCase
{

    public function tearDown()
    {
        parent::tearDown();
        m::close();
    }

    public function testShouldPerformSave()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $builder->shouldReceive('save')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->save());
    }

    public function testShouldPerformUpdateIfAlreadyPersisted()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $builder->shouldReceive('update')
            ->once()
            ->andReturn(true);

        $builder->shouldReceive('save')
            ->never();

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->save());
    }

    public function testShouldTriggerSaveEvents()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent,finishSave]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->shouldReceive('fireModelEvent')
            ->with('saving')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('finishSave')
            ->once()
            ->andReturn(true);

        $builder->shouldReceive('save')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->save());
    }

    public function testShouldFinishPropertlyTheSaveOperation()
    {
        $model = m::mock('Mongolid\Mongolid\Model[syncOriginal,fireModelEvent]');

        $model->shouldReceive('syncOriginal')
            ->once();

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saved');

        $this->assertNull($model->finishSave());
    }

    public function testShouldOverwritesTheOriginalAttributesPropertly()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');

        $model->attributes = ['name' => 'Bob'];
        $model->original   = ['name' => 'John'];

        $this->assertNull($model->syncOriginal());
        $this->assertEquals(['name' => 'Bob'], $model->original);
    }
}
