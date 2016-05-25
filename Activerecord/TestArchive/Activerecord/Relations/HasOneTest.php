<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Relations;

use Activerecord\Table;
use Activerecord\Exceptions\ExceptionUndefinedProperty;
use Activerecord\Exceptions\ExceptionReadOnly;
use Activerecord\Test\Helpers\DatabaseTest;

/**
 * Description of HasOneTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class HasOneTest
        extends \PHPUnit_Framework_TestCase
{

    protected $relationship_name;
    protected $relationship_names = ['has_many',
        'belongs_to',
        'has_one'];

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);

        Venue::$has_one = [];
        Employee::$has_one = [[
        'position']];

        foreach ($this->relationship_names as $name)
        {
            if (\preg_match("/$name/", $this->getName(), $match))
            {
                $this->relationship_name = $match[0];
            }
        }
    }

    public function tearDown()
    {

    }

    protected function getRelationship($type = null)
    {
        if (!$type)
        {
            $type = $this->relationship_name;
        }

        switch ($type)
        {
            case 'has_one';
                $ret = Employee::find(1);
                break;
        }

        return $ret;
    }

    protected function assertDefaultHasOne($employee,
            $association_name = 'position')
    {
        $this->assertTrue($employee->$association_name instanceof Position);
        $this->assertEquals('physicist', $employee->$association_name->title);
        $this->assertNotNull($employee->id, $employee->$association_name->title);
    }

    public function testHasOneBasic()
    {
        $this->assertDefaultHasOne($this->getRelationship());
    }

    public function testHasOneWithExplicitClassName()
    {
        Employee::$has_one = [[
        'explicit_class_name',
        'class_name' => 'Position']];
        $this->assertDefaultHasOne($this->getRelationship(),
                'explicit_class_name');
    }

    public function testHasOneWithSelect()
    {
        Employee::$has_one[0]['select'] = 'title';
        $employee = $this->getRelationship();
        $this->assertDefaultHasOne($employee);

        try
        {
            $employee->position->active;
            $this->fail('expected Exception ExceptionUndefinedProperty');
        }
        catch (ExceptionUndefinedProperty $e)
        {
            $this->assertTrue(\strpos($e->getMessage(), 'active') !== false);
        }
    }

    public function testHasOneWithOrder()
    {
        Employee::$has_one[0]['order'] = 'title';
        $employee = $this->getRelationship();
        $this->assertDefaultHasOne($employee);
        $this->assertSqlHas('ORDER BY title', Position::table()->last_sql);
    }

    public function testHasOneWithConditionsAndNonQualifyingRecord()
    {
        Employee::$has_one[0]['conditions'] = "title = 'programmer'";
        $employee = $this->getRelationship();
        $this->assertEquals(1, $employee->id);
        $this->assertNull($employee->position);
    }

    public function testHasOneWithConditionsAndQualifyingRecord()
    {
        Employee::$has_one[0]['conditions'] = "title = 'physicist'";
        $this->assertDefaultHasOne($this->getRelationship());
    }

    public function testHasOneWithReadOnly()
    {
        Employee::$has_one[0]['readonly'] = true;
        $employee = $this->getRelationship();
        $this->assertDefaultHasOne($employee);

        try
        {
            $employee->position->save();
            $this->fail('expected exception ExceptionReadOnly');
        }
        catch (ExceptionReadOnly $e)
        {

        }

        $employee->position->title = 'new title';
        $this->assertEquals($employee->position->title, 'new title');
    }

    public function testHasOneCanBeSelfReferential()
    {
        Author::$has_one[1] = ['parent_author',
            'class_name' => 'Author',
            'foreign_key' => 'parent_author_id'];
        $author = Author::find(1);
        $this->assertEquals(1, $author->id);
        $this->assertEquals(3, $author->parent_author->id);
    }

    public function testHasOneWithJoins()
    {
        $x = Employee::first(['joins' => ['position']]);
        $this->assertSqlHas('INNER JOIN positions ON(employees.id = positions.employee_id)',
                Employee::table()->last_sql);
    }

    public function testHasOneWithExplicitKeys()
    {
        Book::$has_one = [[
        'explicit_author',
        'class_name' => 'Author',
        'foreign_key' => 'parent_author_id',
        'primary_key' => 'secondary_author_id']];

        $book = Book::find(1);
        $this->assertEquals($book->secondary_author_id,
                $book->explicit_author->parent_author_id);
        $this->assertTrue(\strpos(Table::load('Author')->last_sql,
                        "parent_author_id") !== false);
    }

    public function testHasOneThrough()
    {
        Venue::$has_many = [[
        'events'],
            ['hosts',
                'through' => 'events']];
        $venue = Venue::first();
        $this->assertTrue(\count($venue->hosts) > 0);
    }

}