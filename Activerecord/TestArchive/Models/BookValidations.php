<?php

namespace Test\Models;

class BookValidations
        extends \Activerecord\Model
{

    static $table_name = 'books';
    static $alias_attribute = [
        'name_alias' => 'name',
        'x' => 'secondary_author_id'];
    static $validates_presence_of = [];
    static $validates_uniqueness_of = [];
    static $custom_validator_error_msg = 'failed custom validation';

    // fired for every validation - but only used for custom validation test
    public function validate()
    {
        if ($this->name == 'test_custom_validation')
        {
            $this->errors->add('name', self::$custom_validator_error_msg);
        }
    }

}