<?php

namespace Activerecord\Test\Activerecord;

use Activerecord\Test\Helpers\DatabaseTest;

class ValidatesInclusionAndExclusionOfTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
        BookInclusion::$validates_inclusion_of[0] = [
            'name',
            'in' => [
                'blah',
                'tanker',
                'shark']];
        BookExclusion::$validates_exclusion_of[0] = [
            'name',
            'in' => [
                'blah',
                'alpha',
                'bravo']];
    }

    public function testInclusion()
    {
        $book = new BookInclusion;
        $book->name = 'blah';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testExclusion()
    {
        $book = new BookExclusion;
        $book->name = 'blahh';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInvalidInclusion()
    {
        $book = new BookInclusion;
        $book->name = 'thanker';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
        $book->name = 'alpha ';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testInvalidExclusion()
    {
        $book = new BookExclusion;
        $book->name = 'alpha';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));

        $book = new BookExclusion;
        $book->name = 'bravo';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testInclusionWithNumeric()
    {
        BookInclusion::$validates_inclusion_of[0]['in'] = [0,
            1,
            2];
        $book = new BookInclusion;
        $book->name = 2;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInclusionWithBoolean()
    {
        BookInclusion::$validates_inclusion_of[0]['in'] = [true];
        $book = new BookInclusion;
        $book->name = true;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInclusionWithNull()
    {
        BookInclusion::$validates_inclusion_of[0]['in'] = [null];
        $book = new BookInclusion;
        $book->name = null;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInvalidInclusionWithNumeric()
    {
        BookInclusion::$validates_inclusion_of[0]['in'] = [0,
            1,
            2];
        $book = new BookInclusion;
        $book->name = 5;
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testInclusionWithinOption()
    {
        BookInclusion::$validates_inclusion_of[0] = ['name',
            'within' => ['okay']];
        $book = new BookInclusion;
        $book->name = 'okay';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInclusionScalarValue()
    {
        BookInclusion::$validates_inclusion_of[0] = ['name',
            'within' => 'okay'];
        $book = new BookInclusion;
        $book->name = 'okay';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testValidNull()
    {
        BookInclusion::$validates_inclusion_of[0]['allow_null'] = true;
        $book = new BookInclusion;
        $book->name = null;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testValidBlank()
    {
        BookInclusion::$validates_inclusion_of[0]['allow_blank'] = true;
        $book = new BookInclusion;
        $book->name = '';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testCustomMessage()
    {
        $msg = 'is using a custom message.';
        BookInclusion::$validates_inclusion_of[0]['message'] = $msg;
        BookExclusion::$validates_exclusion_of[0]['message'] = $msg;

        $book = new BookInclusion;
        $book->name = 'not included';
        $book->save();
        $this->assertEquals('is using a custom message.',
                $book->errors->on('name'));
        $book = new BookExclusion;
        $book->name = 'bravo';
        $book->save();
        $this->assertEquals('is using a custom message.',
                $book->errors->on('name'));
    }

}