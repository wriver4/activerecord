<?php

namespace Test\Models;

class VenueCB
        extends \Activerecord\Model
{

    static $table_name = 'venues';
    static $before_save;
    static $before_update;
    static $before_create;
    static $before_validation;
    static $before_destroy = 'before_destroy_using_string';
    static $after_destroy = [
        'after_destroy_one',
        'after_destroy_two'];
    static $after_create;

    // DO NOT add a static $after_construct for this. we are testing
    // auto registration of callback with this
    public function afterConstruct()
    {

    }

    public function nonGenericAfterConstruct()
    {

    }

    public function after_destroy_one()
    {

    }

    public function afterDestroyTwo()
    {

    }

    public function beforeDestroyUsingString()
    {

    }

    public function beforeUpdateHaltExecution()
    {
        return false;
    }

    public function beforeDestroyHaltExecution()
    {
        return false;
    }

    public function beforeCreateHaltExecution()
    {
        return false;
    }

    public function beforeValidationHaltExecution()
    {
        return false;
    }

}