<?php

namespace Activerecord\Test\Activerecord;

use Activerecord\Test\Helpers\DatabaseTest;

class ValidatesFormatOfTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
        BookFormat::$validates_format_of[0] = array(
            'name');
    }

    public function testFormat()
    {
        BookFormat::$validates_format_of[0]['with'] = '/^[a-z\W]*$/';
        $book = new BookFormat(['author_id' => 1,
            'name' => 'testing reg']);
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));

        BookFormat::$validates_format_of[0]['with'] = '/[0-9]/';
        $book = new BookFormat([
            'author_id' => 1,
            'name' => 12]);
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInvalidNull()
    {
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat;
        $book->name = null;
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testInvalidBlank()
    {
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat;
        $book->name = '';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testValidBlankAndAllowBlank()
    {
        BookFormat::$validates_format_of[0]['allow_blank'] = true;
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat(['author_id' => 1,
            'name' => '']);
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testValidNullAndAllowNull()
    {
        BookFormat::$validates_format_of[0]['allow_null'] = true;
        BookFormat::$validates_format_of[0]['with'] = '/[^0-9]/';
        $book = new BookFormat();
        $book->author_id = 1;
        $book->name = null;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    /**
     * @expectedException Activerecord\ValidationsArgumentError
     */
    public function testInvalidLackOfWithKey()
    {
        $book = new BookFormat;
        $book->name = null;
        $book->save();
    }

    /**
     * @expectedException Activerecord\ValidationsArgumentError
     */
    public function testInvalidWithExpressionAsNonString()
    {
        BookFormat::$validates_format_of[0]['with'] = ['test'];
        $book = new BookFormat;
        $book->name = null;
        $book->save();
    }

    public function testInvalidWithExpressionAsNonRegexp()
    {
        BookFormat::$validates_format_of[0]['with'] = 'blah';
        $book = new BookFormat;
        $book->name = 'blah';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testCustomMessage()
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