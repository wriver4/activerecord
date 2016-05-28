<?php

namespace Test;

use Activerecord\Expressions;
use Activerecord\ConnectionManager;
use Activerecord\Exceptions\ExceptionDatabase;

class ExpressionsTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    public function testValues()
    {
        $c = new Expressions(null, 'a=? and b=?', 1, 2);
        $this->assertEquals([1,
            2], $c->values());
    }

    public function testOneVariable()
    {
        $c = new Expressions(null, 'name=?', 'Tito');
        $this->assertEquals('name=?', $c->toString());
        $this->assertEquals(['Tito'], $c->values());
    }

    public function testArrayVariable()
    {
        $c = new Expressions(null, 'name IN(?) and id=?',
                ['Tito',
            'George'], 1);
        $this->assertEquals([[
        'Tito',
        'George'],
            1], $c->values());
    }

    public function testMultipleVariables()
    {
        $c = new Expressions(null, 'name=? and book=?', 'Tito', 'Sharks');
        $this->assertEquals('name=? and book=?', $c->toString());
        $this->assertEquals([
            'Tito',
            'Sharks'], $c->values());
    }

    public function testToString()
    {
        $c = new Expressions(null, 'name=? and book=?', 'Tito', 'Sharks');
        $this->assertEquals('name=? and book=?', $c->toString());
    }

    public function testToStringWithArrayVariable()
    {
        $c = new Expressions(null, 'name IN(?) and id=?',
                ['Tito',
            'George'], 1);
        $this->assertEquals('name IN(?,?) and id=?', $c->toString());
    }

    public function testToStringWithNullOptions()
    {
        $c = new Expressions(null, 'name=? and book=?', 'Tito', 'Sharks');
        $x = null;
        $this->assertEquals('name=? and book=?', $c->toString(false, $x));
    }

    /**
     * @expectedException Activerecord\Exceptions\ExceptionExpressions
     */
    public function testInsufficientVariables()
    {
        $c = new Expressions(null, 'name=? and id=?', 'Tito');
        $c->toString();
    }

    public function testNoValues()
    {
        $c = new Expressions(null, "name='Tito'");
        $this->assertEquals("name='Tito'", $c->toString());
        $this->assertEquals(0, \count($c->values()));
    }

    public function testNullVariable()
    {
        $a = new Expressions(null, 'name=?', null);
        $this->assertEquals('name=?', $a->toString());
        $this->assertEquals([null], $a->values());
    }

    public function testZeroVariable()
    {
        $a = new Expressions(null, 'name=?', 0);
        $this->assertEquals('name=?', $a->toString());
        $this->assertEquals([0], $a->values());
    }

    public function testEmptyArrayVariable()
    {
        $a = new Expressions(null, 'id IN(?)', array());
        $this->assertEquals('id IN(?)', $a->toString());
        $this->assertEquals([[]], $a->values());
    }

    public function testIgnoreInvalidParameterMarker()
    {
        $a = new Expressions(null,
                "question='Do you love backslashes?' and id in(?)",
                [1,
            2]);
        $this->assertEquals("question='Do you love backslashes?' and id in(?,?)",
                $a->toString());
    }

    public function testIgnoreParameterMarkerWithEscapedQuote()
    {
        $a = new Expressions(null,
                "question='Do you love''s backslashes?' and id in(?)",
                [1,
            2]);
        $this->assertEquals("question='Do you love''s backslashes?' and id in(?,?)",
                $a->toString());
    }

    public function testIgnoreParameterMarkerWithBackspaceEscapedQuote()
    {
        $a = new Expressions(null,
                "question='Do you love\\'s backslashes?' and id in(?)",
                [1,
            2]);
        $this->assertEquals("question='Do you love\\'s backslashes?' and id in(?,?)",
                $a->toString());
    }

    public function testSubstitute()
    {
        $a = new Expressions(null, 'name=? and id=?', 'Tito', 1);
        $this->assertEquals("name='Tito' and id=1", $a->toString(true));
    }

    public function testSubstituteQuotesScalarsButNotOthers()
    {
        $a = new Expressions(null, 'id in(?)',
                [1,
            '2',
            3.5]);
        $this->assertEquals("id in(1,'2',3.5)", $a->toString(true));
    }

    public function testSubstituteWhereValueHasQuestionMark()
    {
        $a = new Expressions(null, 'name=? and id=?', '??????', 1);
        $this->assertEquals("name='??????' and id=1", $a->toString(true));
    }

    public function testSubstituteArrayValue()
    {
        $a = new Expressions(null, 'id in(?)', [1,
            2]);
        $this->assertEquals("id in(1,2)", $a->toString(true));
    }

    public function testSubstituteEscapesQuotes()
    {
        $a = new Expressions(null, 'name=? or name in(?)', "Tito's Guild",
                [1,
            "Tito's Guild"]);
        $this->assertEquals("name='Tito''s Guild' or name in(1,'Tito''s Guild')",
                $a->toString(true));
    }

    public function testSubstituteEscapeQuotesWithConnectionsEscapeMethod()
    {
        try
        {
            $conn = ConnectionManager::getConnection();
        }
        catch (ExceptionDatabase $e)
        {
            $this->markTestSkipped('failed to connect. '.$e->getMessage());
        }
        $a = new Expressions(null, 'name=?', "Tito's Guild");
        $a->setConnection($conn);
        $escaped = $conn->escape("Tito's Guild");
        $this->assertEquals("name=$escaped", $a->toString(true));
    }

    public function testBind()
    {
        $a = new Expressions(null, 'name=? and id=?', 'Tito');
        $a->bind(2, 1);
        $this->assertEquals(['Tito',
            1], $a->values());
    }

    public function testBindOverwriteExisting()
    {
        $a = new Expressions(null, 'name=? and id=?', 'Tito', 1);
        $a->bind(2, 99);
        $this->assertEquals(['Tito',
            99], $a->values());
    }

    /**
     * @expectedException Activerecord\Exceptions\ExceptionExpressions
     */
    public function testBindInvalidParameterNumber()
    {
        $a = new Expressions(null, 'name=?');
        $a->bind(0, 99);
    }

    public function testSubsituteUsingAlternateValues()
    {
        $a = new Expressions(null, 'name=?', 'Tito');
        $this->assertEquals("name='Tito'", $a->toString(true));
        $x = ['values' => ['Hocus']];
        $this->assertEquals("name='Hocus'", $a->toString(true, $x));
    }

    public function testNullValue()
    {
        $a = new Expressions(null, 'name=?', null);
        $this->assertEquals('name=NULL', $a->toString(true));
    }

    public function testHashWithDefaultGlue()
    {
        $a = new Expressions(null, ['id' => 1,
            'name' => 'Tito']);
        $this->assertEquals('id=? AND name=?', $a->toString());
    }

    public function testHashWithGlue()
    {
        $a = new Expressions(null, ['id' => 1,
            'name' => 'Tito'], ', ');
        $this->assertEquals('id=?, name=?', $a->toString());
    }

    public function testHashWithArray()
    {
        $a = new Expressions(null,
                ['id' => 1,
            'name' => ['Tito',
                'Mexican']]);
        $this->assertEquals('id=? AND name IN(?,?)', $a->toString());
    }

}