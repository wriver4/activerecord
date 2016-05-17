<?php

class ValidatesPresenceOfTest
        extends \Test\Helpers\DatabaseTest
{

    public function testPresence()
    {
        $book = new BookPresence(['name' => 'blah']);
        $this->assertFalse($book->isInvalid());
    }

    public function testPresenceOnDateFieldIsValid()
    {
        $author = new AuthorPresence(['some_date' => '2010-01-01']);
        $this->assertTrue($author->isValid());
    }

    public function testPresenceOnDateFieldIsNotValid()
    {
        $author = new AuthorPresence();
        $this->assertFalse($author->isValid());
    }

    public function testInvalidNull()
    {
        $book = new BookPresence(['name' => null]);
        $this->assertTrue($book->isInvalid());
    }

    public function testInvalidBlank()
    {
        $book = new BookPresence(['name' => '']);
        $this->assertTrue($book->isInvalid());
    }

    public function testValidWhiteSpace()
    {
        $book = new BookPresence(['name' => ' ']);
        $this->assertFalse($book->isInvalid());
    }

    public function testCustomMessage()
    {
        BookPresence::$validates_presence_of[0]['message'] = 'is using a custom message.';

        $book = new BookPresence(['name' => null]);
        $book->isValid();
        $this->assertEquals('is using a custom message.',
                $book->errors->on('name'));
    }

    public function testValidZero()
    {
        $book = new BookPresence(['name' => 0]);
        $this->assertTrue($book->isValid());
    }

}