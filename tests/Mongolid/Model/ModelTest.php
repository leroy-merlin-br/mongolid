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

    public function testShouldNotPerformSaveIfSavingEventReturnFalse()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saving')
            ->andReturn(false);

        $builder->shouldReceive('save')
            ->never();

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertFalse($model->save());
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

    public function testShouldPerformUpdate()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $builder->shouldReceive('update')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->update());
    }

    public function testShouldNotPerformUpdateIfUpdatingEventReturnFalse()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('updating')
            ->andReturn(false);

        $builder->shouldReceive('update')
            ->never();

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertFalse($model->update());
    }

    public function testShouldPerformSaveIfNotExistsAtDB()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $builder->shouldReceive('save')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->update());
    }

    public function testShouldTriggerUpdateEvent()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $model->shouldReceive('fireModelEvent')
            ->with('updating')
            ->once()
            ->andReturn(true);

        $builder->shouldReceive('update')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->update());
    }

    public function testShouldPerformInsert()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $builder->shouldReceive('insert')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->insert());
    }

    public function testShouldNotPerformInsertIfSavingEventReturnFalse()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('saving')
            ->andReturn(false);

        $builder->shouldReceive('insert')
            ->never();

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertFalse($model->insert());
    }

    public function testShouldTriggerInsertEvent()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $model->shouldReceive('fireModelEvent')
            ->with('saving')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('fireModelEvent')
            ->with('creating')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('fireModelEvent')
            ->with('saved', false)
            ->once()
            ->andReturn(true);

        $model->shouldReceive('fireModelEvent')
            ->with('created', false)
            ->once()
            ->andReturn(true);

        $builder->shouldReceive('insert')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->insert());
    }

    public function testShouldPerformDelete()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $builder->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->delete());
    }

    public function testShouldNotPerformDeleteIfDeletingEventReturnFalse()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $model->shouldReceive('fireModelEvent')
            ->once()
            ->with('deleting')
            ->andReturn(false);

        $builder->shouldReceive('delete')
            ->never();

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertFalse($model->delete());
    }

    public function testShouldTriggerDeleteEvent()
    {
        $model   = m::mock('Mongolid\Mongolid\Model[fireModelEvent]');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $model->exists = true;

        $model->shouldReceive('fireModelEvent')
            ->with('deleting')
            ->once()
            ->andReturn(true);

        $model->shouldReceive('fireModelEvent')
            ->with('deleted', false)
            ->once()
            ->andReturn(true);

        $builder->shouldReceive('delete')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->delete());
    }

    public function testShouldCallUndefinedMethodsIntoModelClassAtQueryBuilder()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $builder->shouldReceive('canPersistInstance')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue($model->canPersistInstance($model));
    }

    public function testShouldCallStaticUndefinedMethodsIntoModelClassAtQueryBuilder()
    {
        $model   = Ioc::make('Mongolid\Mongolid\Model');
        $builder = m::mock('Mongolid\Mongolid\Query\Builder');

        $builder->shouldReceive('canPersistInstance')
            ->once()
            ->andReturn(true);

        Ioc::instance('Mongolid\Mongolid\Query\Builder', $builder);

        $this->assertTrue(\Mongolid\Mongolid\Model::canPersistInstance($model));
    }
}
