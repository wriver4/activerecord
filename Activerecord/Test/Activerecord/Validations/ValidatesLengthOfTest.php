<?php

namespace Activerecord\Test\Activerecord;

use Activerecord\Exceptions\ExceptionValidation;
use Activerecord\Test\Helpers\DatabaseTest;

class ValidatesLengthOfTest
        extends DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
        BookLength::$validates_length_of[0] = ['name',
            'allow_blank' => false,
            'allow_null' => false];
    }

    public function testWithin()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            5];
        $book = new BookLength;
        $book->name = '12345';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testWithinErrorMessage()
    {
        BookLength::$validates_length_of[0]['within'] = [2,
            5];
        $book = new BookLength();
        $book->name = '1';
        $book->isValid();
        $this->assertEquals(['Name is too short (minimum is 2 characters)'],
                $book->errors->fullMessages());

        $book->name = '123456';
        $book->isValid();
        $this->assertEquals(['Name is too long (maximum is 5 characters)'],
                $book->errors->fullMessages());
    }

    public function testWithinCustomErrorMessage()
    {
        BookLength::$validates_length_of[0]['within'] = [2,
            5];
        BookLength::$validates_length_of[0]['too_short'] = 'is too short';
        BookLength::$validates_length_of[0]['message'] = 'is not between 2 and 5 characters';
        $book = new BookLength();
        $book->name = '1';
        $book->isValid();
        $this->assertEquals(['Name is not between 2 and 5 characters'],
                $book->errors->fullMessages());

        $book->name = '123456';
        $book->isValid();
        $this->assertEquals(['Name is not between 2 and 5 characters'],
                $book->errors->fullMessages());
    }

    public function testValidIn()
    {
        BookLength::$validates_length_of[0]['in'] = [1,
            5];
        $book = new BookLength;
        $book->name = '12345';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testAliasedSizeOf()
    {
        BookSize::$validates_size_of = BookLength::$validates_length_of;
        BookSize::$validates_size_of[0]['within'] = [1,
            5];
        $book = new BookSize;
        $book->name = '12345';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInvalidWithinAndIn()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];
        $book = new BookLength;
        $book->name = 'four';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));

        $this->setUp();
        BookLength::$validates_length_of[0]['in'] = [1,
            3];
        $book = new BookLength;
        $book->name = 'four';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
    }

    public function testValidNull()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];
        BookLength::$validates_length_of[0]['allow_null'] = true;

        $book = new BookLength;
        $book->name = null;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testValidBlank()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];
        BookLength::$validates_length_of[0]['allow_blank'] = true;

        $book = new BookLength;
        $book->name = '';
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testInvalidBlank()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];

        $book = new BookLength;
        $book->name = '';
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
        $this->assertEquals('is too short (minimum is 1 characters)',
                $book->errors->on('name'));
    }

    public function testInvalidNullWithin()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];

        $book = new BookLength;
        $book->name = null;
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
        $this->assertEquals('is too short (minimum is 1 characters)',
                $book->errors->on('name'));
    }

    public function testInvalidNullMinimum()
    {
        BookLength::$validates_length_of[0]['minimum'] = 1;

        $book = new BookLength;
        $book->name = null;
        $book->save();
        $this->assertTrue($book->errors->isInvalid('name'));
        $this->assertEquals('is too short (minimum is 1 characters)',
                $book->errors->on('name'));
    }

    public function testValidNullMaximum()
    {
        BookLength::$validates_length_of[0]['maximum'] = 1;

        $book = new BookLength;
        $book->name = null;
        $book->save();
        $this->assertFalse($book->errors->isInvalid('name'));
    }

    public function testFloatAsImpossibleRangeOption()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3.6];
        $book = new BookLength;
        $book->name = '123';
        try
        {
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('maximum value cannot use a float for length.',
                    $e->getMessage());
        }

        $this->setUp();
        BookLength::$validates_length_of[0]['is'] = 1.8;
        $book = new BookLength;
        $book->name = '123';
        try
        {
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('is value cannot use a float for length.',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    public function testSignedIntegerAsImpossibleWithinOption()
    {
        BookLength::$validates_length_of[0]['within'] = [-1,
            3];

        $book = new BookLength;
        $book->name = '123';
        try
        {
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('minimum value cannot use a signed integer.',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    public function testNotArrayAsImpossibleRangeOption()
    {
        BookLength::$validates_length_of[0]['within'] = 'string';
        $book = new BookLength;
        $book->name = '123';
        try
        {
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('within must be an array composing a range of numbers with key [0] being less than key [1]',
                    $e->getMessage());
        }

        $this->setUp();
        BookLength::$validates_length_of[0]['in'] = 'string';
        $book = new BookLength;
        $book->name = '123';
        try
        {
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('in must be an array composing a range of numbers with key [0] being less than key [1]',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    public function testSignedIntegerAsImpossibleIsOption()
    {
        BookLength::$validates_length_of[0]['is'] = -8;

        $book = new BookLength;
        $book->name = '123';
        try
        {
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('is value cannot use a signed integer.',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    public function testLackOfOption()
    {
        try
        {
            $book = new BookLength;
            $book->name = null;
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('Range unspecified.  Specify the [within], [maximum], or [is] option.',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    public function testTooManyOptions()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];
        BookLength::$validates_length_of[0]['in'] = [1,
            3];

        try
        {
            $book = new BookLength;
            $book->name = null;
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('Too many range options specified.  Choose only one.',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    public function testTooManyOptionsWithDifferentOptionTypes()
    {
        BookLength::$validates_length_of[0]['within'] = [1,
            3];
        BookLength::$validates_length_of[0]['is'] = 3;

        try
        {
            $book = new BookLength;
            $book->name = null;
            $book->save();
        }
        catch (ExceptionValidation $e)
        {
            $this->assertEquals('Too many range options specified.  Choose only one.',
                    $e->getMessage());
            return;
        }

        $this->fail('An expected exception has not be raised.');
    }

    /**
     * @expectedException ExceptionValidations
     */
    public function testWithOptionAsNonNumeric()
    {
        BookLength::$validates_length_of[0]['with'] = ['test'];

        $book = new BookLength;
        $book->name = null;
        $book->save();
    }

    /**
     * @expectedException ExceptionValidations
     */
    public function testWithOptionAsNonNumericNonArray()
    {
        BookLength::$validates_length_of[0]['with'] = 'test';

        $book = new BookLength;
        $book->name = null;
        $book->save();
    }

    public function testValidatesLengthOfMaximum()
    {
        BookLength::$validates_length_of[0] = [
            'name',
            'maximum' => 10];
        $book = new BookLength(['name' => '12345678901']);
        $book->isValid();
        $this->assertEquals(["Name is too long (maximum is 10 characters)"],
                $book->errors->fullMessages());
    }

    public function testValidatesLengthOfMinimum()
    {
        BookLength::$validates_length_of[0] = ['name',
            'minimum' => 2];
        $book = new BookLength(['name' => '1']);
        $book->isValid();
        $this->assertEquals(["Name is too short (minimum is 2 characters)"],
                $book->errors->fullMessages());
    }

    public function testValidatesLengthOfMinMaxCustomMessage()
    {
        BookLength::$validates_length_of[0] = ['name',
            'maximum' => 10,
            'message' => 'is far too long'];
        $book = new BookLength(['name' => '12345678901']);
        $book->isValid();
        $this->assertEquals(["Name is far too long"],
                $book->errors->fullMessages());

        BookLength::$validates_length_of[0] = [
            'name',
            'minimum' => 10,
            'message' => 'is far too short'];
        $book = new BookLength(['name' => '123456789']);
        $book->isValid();
        $this->assertEquals(["Name is far too short"],
                $book->errors->fullMessages());
    }

    public function testValidatesLengthOfMinMaxCustomMessageOverridden()
    {
        BookLength::$validates_length_of[0] = ['name',
            'minimum' => 10,
            'too_short' => 'is too short',
            'message' => 'is custom message'];
        $book = new BookLength(['name' => '123456789']);
        $book->isValid();
        $this->assertEquals(["Name is custom message"],
                $book->errors->fullMessages());
    }

    public function testValidatesLengthOfIs()
    {
        BookLength::$validates_length_of[0] = ['name',
            'is' => 2];
        $book = new BookLength(['name' => '123']);
        $book->isValid();
        $this->assertEquals(["Name is the wrong length (should be 2 characters)"],
                $book->errors->fullMessages());
    }

}