<?php

namespace Test\Activerecord;

class ValidatesFormatOfTest
        extends Test\Helpers\DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
        BookFormat::$validates_format_of[0] = array(
            'name');
    }

    public function test_format()
    {
        BookFormat::$validates_format_of[0]['with'] = '/^[a-z\W]*$/';
        $book = new BookFormat(array(
            'author_id' => 1,
            'name' => 'testing reg'));
        $book->save();
        $this->assertFalse($book->errors->is_invalid('name'));

        BookFormat::$validates_format_of[0]['with'] = '/[0-9]/';
        $book = new BookFormat(array(
            'author_id' => 1,
            'name' => 12));
        $book->save();
        $this->assertFalse($book->errors->is_invalid('name'));
    }

    public function testInvalid_null()
    {
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat;
        $book->name = null;
        $book->save();
        $this->assertTrue($book->errors->is_invalid('name'));
    }

    public function testInvalid_blank()
    {
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat;
        $book->name = '';
        $book->save();
        $this->assertTrue($book->errors->is_invalid('name'));
    }

    public function test_valid_blank_andallow_blank()
    {
        BookFormat::$validates_format_of[0]['allow_blank'] = true;
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat(array(
            'author_id' => 1,
            'name' => ''));
        $book->save();
        $this->assertFalse($book->errors->is_invalid('name'));
    }

    public function test_valid_null_and_allow_null()
    {
        BookFormat::$validates_format_of[0]['allow_null'] = true;
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat();
        $book->author_id = 1;
        $book->name = null;
        $book->save();
        $this->assertFalse($book->errors->is_invalid('name'));
    }

    /**
     * @expectedException Activerecord\ValidationsArgumentError
     */
    public function testInvalid_lack_of_with_key()
    {
        $book = new BookFormat;
        $book->name = null;
        $book->save();
    }

    /**
     * @expectedException Activerecord\ValidationsArgumentError
     */
    public function testInvalid_with_expression_as_non_string()
    {
        BookFormat::$validates_format_of[0]['with'] = array(
            'test');
        $book = new BookFormat;
        $book->name = null;
        $book->save();
    }

    public function testInvalid_with_expression_as_non_regexp()
    {
        BookFormat::$validates_format_of[0]['with'] = 'blah';
        $book = new BookFormat;
        $book->name = 'blah';
        $book->save();
        $this->assertTrue($book->errors->is_invalid('name'));
    }

    public function test_custom_message()
    {
        BookFormat::$validates_format_of[0]['message'] = 'is using a custom message.';
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';

        $book = new BookFormat;
        $book->name = null;
        $book->save();
        $this->assertEquals('is using a custom message.',
                $book->errors->on('name'));
    }

}