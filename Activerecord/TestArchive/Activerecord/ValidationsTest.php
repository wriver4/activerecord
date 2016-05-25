<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord;

use Activerecord\Test\Helpers\DatabaseTest;

/**
 * Description of ValidationsTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class ValidationsTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);

        BookValidations::$validates_presence_of[0] = 'name';
        BookValidations::$validates_uniqueness_of[0] = 'name';

        ValuestoreValidations::$validates_uniqueness_of[0] = 'key';
    }

    public function tearDown()
    {

    }

    public function testIsValidInvokesValidations()
    {
        $book = new Book;
        $this->assertTrue(empty($book->errors));
        $book->isValid();
        $this->assertFalse(empty($book->errors));
    }

    public function testIsValidReturnsTrueIfNoValidationsExist()
    {
        $book = new Book;
        $this->assertTrue($book->isValid());
    }

    public function testIsValidReturnsFalseIfFailedValidations()
    {
        $book = new BookValidations;
        $this->assertFalse($book->isValid());
    }

    public function testIsInvalid()
    {
        $book = new Book();
        $this->assertFalse($book->isInvalid());
    }

    public function testIsInvalidIsTrue()
    {
        $book = new BookValidations();
        $this->assertTrue($book->isInvalid());
    }

    public function testIsIterable()
    {
        $book = new BookValidations();
        $book->isValid();

        foreach ($book->errors as $name => $message)
        {
            $this->assertEquals("Name can't be blank", $message);
        }
    }

    public function testFullMessages()
    {
        $book = new BookValidations();
        $book->isValid();

        $this->assertEquals(["Name can't be blank"],
                \array_values($book->errors->fullMessages(['hash' => true])));
    }

    public function testToArray()
    {
        $book = new BookValidations();
        $book->isValid();

        $this->assertEquals(["name" => ["Name can't be blank"]],
                $book->errors->toArray());
    }

    public function testToString()
    {
        $book = new BookValidations();
        $book->isValid();
        $book->errors->add('secondary_author_id', "is invalid");

        $this->assertEquals("Name can't be blank\nSecondary author id is invalid",
                (string) $book->errors);
    }

    public function testValidatesUniquenessOf()
    {
        BookValidations::create(['name' => 'bob']);
        $book = BookValidations::create(['name' => 'bob']);

        $this->assertEquals(["Name must be unique"],
                $book->errors->fullMessages());
        $this->assertEquals(1,
                BookValidations::count(['conditions' => "name='bob'"]));
    }

    public function testValidatesUniquenessOfExcludesSelf()
    {
        $book = BookValidations::first();
        $this->assertEquals(true, $book->isValid());
    }

    public function testValidatesUniquenessOfWithMultipleFields()
    {
        BookValidations::$validates_uniqueness_of[0] = [[
        'name',
        'special']];
        $book1 = BookValidations::first();
        $book2 = new BookValidations([
            'name' => $book1->name,
            'special' => $book1->special + 1]);
        $this->assertTrue($book2->isValid());
    }

    public function testValidatesUniquenessOfWithMultipleFieldsIsNotUnique()
    {
        BookValidations::$validates_uniqueness_of[0] = [[
        'name',
        'special']];
        $book1 = BookValidations::first();
        $book2 = new BookValidations([
            'name' => $book1->name,
            'special' => $book1->special]);
        $this->assertFalse($book2->isValid());
        $this->assertEquals(['Name and special must be unique'],
                $book2->errors->fullMessages());
    }

    public function testValidatesUniquenessOfWorksWithAliasAttribute()
    {
        BookValidations::$validates_uniqueness_of[0] = [[
        'name_alias',
        'x']];
        $book = BookValidations::create([
                    'name_alias' => 'Another Book',
                    'x' => 2]);
        $this->assertFalse($book->isValid());
        $this->assertEquals(['Name alias and x must be unique'],
                $book->errors->fullMessages());
    }

    public function testValidatesUniquenessOfWorksWithMysqlReservedWordAsColumnName()
    {
        ValuestoreValidations::create([
            'key' => 'GA_KEY',
            'value' => 'UA-1234567-1']);
        $valuestore = ValuestoreValidations::create([
                    'key' => 'GA_KEY',
                    'value' => 'UA-1234567-2']);

        $this->assertEquals(["Key must be unique"],
                $valuestore->errors->fullMessages());
        $this->assertEquals(1,
                ValuestoreValidations::count(['conditions' => "`key`='GA_KEY'"]));
    }

    public function testGetValidationRules()
    {
        $validators = BookValidations::first()->getValidationRules();
        $this->assertTrue(in_array(array(
            'validator' => 'validates_presence_of'), $validators['name']));
    }

    public function testModelIsNulledOutToPreventMemoryLeak()
    {
        $book = new BookValidations();
        $book->isValid();
        $this->assertTrue(\strpos(serialize($book->errors), 'model";N;') !== false);
    }

    public function testValidationsTakesStrings()
    {
        BookValidations::$validates_presence_of = ['numeric_test',
            ['special'],
            'name'];
        $book = new BookValidations([
            'numeric_test' => 1,
            'special' => 1]);
        $this->assertFalse($book->isValid());
    }

    public function testGh131CustomValidation()
    {
        $book = new BookValidations(['name' => 'test_custom_validation']);
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
        $this->assertEquals(BookValidations::$custom_validator_error_msg,
                $book->errors->on('name'));
    }

}