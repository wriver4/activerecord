<?php

namespace Activerecord\Test\Activerecord;

use Activerecord\Test\Helpers\DatabaseTest;

class ModelCallbackTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);

        $this->venue = new Venue();
        $this->callback = Venue::table()->callback;
    }

    public function tearDown()
    {

    }

    public function registerAndInvokeCallbacks($callbacks, $return, $closure)
    {
        if (!\is_array($callbacks))
        {
            $callbacks = [$callbacks];
        }

        $fired = [];

        foreach ($callbacks as $name)
                $this->callback->register($name,
                    function($model) use (&$fired, $name, $return)
            {
                $fired[] = $name;
                return $return;
            });

        $closure($this->venue);
        return \array_intersect($callbacks, $fired);
    }

    public function assertFires($callbacks, $closure)
    {
        $executed = $this->registerAndInvokeCallbacks($callbacks, true, $closure);
        $this->assertEquals(\count($callbacks), \count($executed));
    }

    public function assertDoesNotFire($callbacks, $closure)
    {
        $executed = $this->registerAndInvokeCallbacks($callbacks, true, $closure);
        $this->assertEquals(0, \count($executed));
    }

    public function assertFiresReturnsFalse($callbacks, $only_fire, $closure)
    {
        if (!\is_array($only_fire))
        {
            $only_fire = [$only_fire];
        }

        $executed = $this->registerAndInvokeCallbacks($callbacks, false,
                $closure);
        \sort($only_fire);
        $intersect = \array_intersect($only_fire, $executed);
        \sort($intersect);
        $this->assertEquals($only_fire, $intersect);
    }

    public function testAfterConstructFiresByDefault()
    {
        $this->assert_fires('after_construct',
                function($model)
        {
            new Venue();
        });
    }

    public function testFireValidationCallbacksOnInsert()
    {
        $this->assertFires(['before_validation',
            'after_validation',
            'before_validation_on_create',
            'after_validation_on_create'],
                function($model)
        {
            $model = new Venue();
            $model->save();
        });
    }

    public function testFireValidationCallbacksOnUpdate()
    {
        $this->assertFires(['before_validation',
            'after_validation',
            'before_validation_on_update',
            'after_validation_on_update'],
                function($model)
        {
            $model = Venue::first();
            $model->save();
        });
    }

    public function testValidationCallBacksNotFiredDueToBypassingValidations()
    {
        $this->assertDoesNotFire('before_validation',
                function($model)
        {
            $model->save(false);
        });
    }

    public function testBeforeValidationReturningFalseCancelsCallbacks()
    {
        $this->assertFiresReturnsFalse([
            'before_validation',
            'after_validation'], 'before_validation',
                function($model)
        {
            $model->save();
        });
    }

    public function testFiresBeforeSaveAndBeforeUpdateWhenUpdating()
    {
        $this->assertFires(['before_save',
            'before_update'],
                function($model)
        {
            $model = Venue::first();
            $model->name = "something new";
            $model->save();
        });
    }

    public function testBeforeSaveReturningFalseCancelsCallbacks()
    {
        $this->assertFiresReturnsFalse(['before_save',
            'before_create'], 'before_save',
                function($model)
        {
            $model = new Venue();
            $model->save();
        });
    }

    public function testDestroy()
    {
        $this->assertFires(['before_destroy',
            'after_destroy'],
                function($model)
        {
            $model->delete();
        });
    }

}