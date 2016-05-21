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
 * Description of BelongsToTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class BelongsToTest
        extends DatabaseTest
{

    protected $relationship_name = 'belongs_to';

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);

        Event::$belongs_to = [[
        'venue'],
            ['host']];
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
            case 'belongs_to';
                $ret = Event::find(5);
                break;
        }

        return $ret;
    }

    protected function assertDefaultBelongsTo($event,
            $association_name = 'venue')
    {
        $this->assertTrue($event->$association_name instanceof Venue);
        $this->assertEquals(5, $event->id);
        $this->assertEquals('West Chester', $event->$association_name->city);
        $this->assertEquals(6, $event->$association_name->id);
    }

    public function testBelongsToBasic()
    {
        $this->assertDefaultBelongsTo($this->getRelationship());
    }

    public function testBelongsToReturnsNullWhenNoRecord()
    {
        $event = Event::find(6);
        $this->assertNull($event->venue);
    }

    public function testBelongsToReturnsNullWhenForeignKeyIsNull()
    {
        $event = Event::create(['title' => 'venueless event']);
        $this->assertNull($event->venue);
    }

    public function testBelongsToWithExplicitClassName()
    {
        Event::$belongs_to = [[
        'explicit_class_name',
        'class_name' => 'Venue']];
        $this->assertDefaultBelongsTo($this->getRelationship(),
                'explicit_class_name');
    }

    public function testBelongsToWithExplicitForeignKey()
    {
        $old = Book::$belongs_to;
        Book::$belongs_to = [[
        'explicit_author',
        'class_name' => 'Author',
        'foreign_key' => 'secondary_author_id']];

        $book = Book::find(1);
        $this->assertEquals(2, $book->secondary_author_id);
        $this->assertEquals($book->secondary_author_id,
                $book->explicit_author->author_id);

        Book::$belongs_to = $old;
    }

    public function testBelongsToWithSelect()
    {
        Event::$belongs_to[0]['select'] = 'id, city';
        $event = $this->getRelationship();
        $this->assertDefaultBelongsTo($event);

        try
        {
            $event->venue->name;
            $this->fail('expected Exception ExceptionUndefinedProperty');
        }
        catch (ExceptionUndefinedProperty $e)
        {
            $this->assertTrue(strpos($e->getMessage(), 'name') !== false);
        }
    }

    public function testBelongsToWithReadonly()
    {
        Event::$belongs_to[0]['readonly'] = true;
        $event = $this->getRelationship();
        $this->assertDefaultBelongsTo($event);

        try
        {
            $event->venue->save();
            $this->fail('expected exception ExceptionReadOnly');
        }
        catch (ExceptionReadOnly $e)
        {

        }

        $event->venue->name = 'new name';
        $this->assertEquals($event->venue->name, 'new name');
    }

    public function testBelongsToWithPluralAttributeName()
    {
        Event::$belongs_to = [[
        'venues',
        'class_name' => 'Venue']];
        $this->assertDefaultBelongsTo($this->getRelationship(), 'venues');
    }

    public function testBelongsToWithConditionsAndNonQualifyingRecord()
    {
        Event::$belongs_to[0]['conditions'] = "state = 'NY'";
        $event = $this->getRelationship();
        $this->assertEquals(5, $event->id);
        $this->assertNull($event->venue);
    }

    public function testBelongsToWithConditionsAndQualifyingRecord()
    {
        Event::$belongs_to[0]['conditions'] = "state = 'PA'";
        $this->assertDefaultBelongsTo($this->getRelationship());
    }

    public function testBelongsToBuildAssociation()
    {
        $event = $this->getRelationship();
        $values = ['city' => 'Richmond',
            'state' => 'VA'];
        $venue = $event->build_Venue($values);
        $this->assertEquals($values,
                \array_intersect_key($values, $venue->attributes()));
    }

    public function testBelongsToCreateAssociation()
    {
        $event = $this->getRelationship();
        $values = ['city' => 'Richmond',
            'state' => 'VA',
            'name' => 'Club 54',
            'address' => '123 street'];
        $venue = $event->create_venue($values);
        $this->assertNotNull($venue->id);
    }

    public function testBelongsToCanBeSelfReferential()
    {
        Author::$belongs_to = [[
        'parent_author',
        'class_name' => 'Author',
        'foreign_key' => 'parent_author_id']];
        $author = Author::find(1);
        $this->assertEquals(1, $author->id);
        $this->assertEquals(3, $author->parent_author->id);
    }

    public function testBelongsToWithAnInvalidOption()
    {
        Event::$belongs_to[0]['joins'] = 'venue';
        $event = Event::first()->venue;
        $this->assertSqlDoesNotHas('INNER JOIN venues ON(events.venue_id = venues.id)',
                Event::table()->last_sql);
    }

    public function testEagerLoadingBelongsTo()
    {
        $events = Event::find([1,
                    2,
                    3,
                    5,
                    7], ['include' => 'venue']);

        foreach ($events as $event)
        {
            $this->assertEquals($event->venue_id, $event->venue->id);
        }

        $this->assertSqlHas("WHERE id IN(?,?,?,?,?)",
                Activerecord\Table::load('Venue')->last_sql);
    }

    public function testEagerLoadingBelongsToArrayOfIncludes()
    {
        $events = Event::find([1,
                    2,
                    3,
                    5,
                    7], ['include' => ['venue',
                        'host']]);

        foreach ($events as $event)
        {
            $this->assertEquals($event->venue_id, $event->venue->id);
            $this->assertEquals($event->host_id, $event->host->id);
        }

        $this->assertSqlHas("WHERE id IN(?,?,?,?,?)",
                Table::load('Event')->last_sql);
        $this->assertSqlHas("WHERE id IN(?,?,?,?,?)",
                Table::load('Host')->last_sql);
        $this->assertSqlHas("WHERE id IN(?,?,?,?,?)",
                Table::load('Venue')->last_sql);
    }

    public function testEagerLoadingBelongsToNested()
    {
        Author::$has_many = array(
            array(
                'awesome_people'));

        $books = Book::find([1,
                    2], ['include' => ['author' => ['awesome_people']]]);

        $assocs = ['author',
            'awesome_people'];

        foreach ($books as $book)
        {
            $this->assertEquals($book->author_id, $book->author->author_id);
            $this->assertEquals($book->author->author_id,
                    $book->author->awesome_people[0]->author_id);
        }

        $this->assertSqlHas("WHERE book_id IN(?,?)",
                Table::load('Book')->last_sql);
        $this->assertSqlHas("WHERE author_id IN(?,?)",
                Table::load('Author')->last_sql);
        $this->assertSqlHas("WHERE author_id IN(?,?)",
                Table::load('AwesomePerson')->last_sql);
    }

    public function testEagerLoadingBelongsToWithNoRelatedRows()
    {
        $e1 = Event::create(['venue_id' => 200,
                    'host_id' => 200,
                    'title' => 'blah',
                    'type' => 'Music']);
        $e2 = Event::create(['venue_id' => 200,
                    'host_id' => 200,
                    'title' => 'blah2',
                    'type' => 'Music']);

        $events = Event::find([
                    $e1->id,
                    $e2->id], ['include' => 'venue']);

        foreach ($events as $e)
        {
            $this->assertNull($e->venue);
        }

        $this->assertSqlHas("WHERE id IN(?,?)", Table::load('Event')->last_sql);
        $this->assertSqlHas("WHERE id IN(?,?)", Table::load('Venue')->last_sql);
    }

    public function testGh23RelationshipsWithJoinsToSameTableShouldAliasTableName()
    {
        $old = Book::$belongs_to;
        Book::$belongs_to = [[
        'from_',
        'class_name' => 'Author',
        'foreign_key' => 'author_id'],
            ['to',
                'class_name' => 'Author',
                'foreign_key' => 'secondary_author_id'],
            ['another',
                'class_name' => 'Author',
                'foreign_key' => 'secondary_author_id']];

        $c = Table::load('Book')->conn;

        $select = "books.*, authors.name as to_author_name, {$c->quoteName('from_')}.name as from_author_name, {$c->quoteName('another')}.name as another_author_name";
        $book = Book::find(2,
                        ['joins' => ['to',
                        'from_',
                        'another'],
                    'select' => $select]);

        $this->assertNotNull($book->from_author_name);
        $this->assertNotNull($book->to_author_name);
        $this->assertNotNull($book->another_author_name);
        Book::$belongs_to = $old;
    }

}