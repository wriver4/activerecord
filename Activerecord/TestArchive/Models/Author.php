<?php

namespace Test\Models;

class Author
        extends \Activerecord\Model
{

    static $pk = 'author_id';
//	static $has_one = array(array('awesome_person', 'foreign_key' => 'author_id', 'primary_key' => 'author_id'),
//	array('parent_author', 'class_name' => 'Author', 'foreign_key' => 'parent_author_id'));
    static $has_many = ['books'];
    static $has_one = [
        [
            'awesome_person',
            'foreign_key' => 'author_id',
            'primary_key' => 'author_id'],
        [
            'parent_author',
            'class_name' => 'Author',
            'foreign_key' => 'parent_author_id']];
    static $belongs_to = [];

    public function setPassword($plaintext)
    {
        $this->encrypted_password = \md5($plaintext);
    }

    public function setName($value)
    {
        $value = \strtoupper($value);
        $this->assignAttribute('name', $value);
    }

    public function returnSomething()
    {
        return ["sharks" => "lasers"];
    }

}