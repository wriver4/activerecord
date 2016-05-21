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
 * Description of HasManyTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class HasManyTest
        extends DatabaseTest
{

    protected $relationship_name;
    protected $relationship_names = ['has_many'];

    public function setUp($connection_name)
    {
        parent::setUp($connection_name);

        Venue::$has_many = [[
        'events',
        'order' => 'id asc'],
            ['hosts',
                'through' => 'events',
                'order' => 'hosts.id asc']];
        Host::$has_many = [[
        'events',
        'order' => 'id asc']];

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

            case 'has_many';
                $ret = Venue::find(2);
                break;
        }

        return $ret;
    }

    protected function assertDefaultHasMany($venue, $association_name = 'events')
    {
        $this->assertEquals(2, $venue->id);
        $this->assertTrue(count($venue->$association_name) > 1);
        $this->assertEquals('Yeah Yeah Yeahs',
                $venue->{$association_name}[0]->title);
    }

    public function testHasManyBasic()
    {
        $this->assertDefaultHasMany($this->getRelationship());
    }

    public function testHasManyBuildAssociation()
    {
        $author = Author::first();
        $this->assertEquals($author->id, $author->build_books()->author_id);
        $this->assertEquals($author->id, $author->build_book()->author_id);
    }

    public function testHasManyWithExplicitClassName()
    {
        Venue::$has_many = [[
        'explicit_class_name',
        'class_name' => 'Event',
        'order' => 'id asc']];

        $this->assertDefaultHasMany($this->getRelationship(),
                'explicit_class_name');
    }

    public function testHasManyWithSelect()
    {
        Venue::$has_many[0]['select'] = 'title, type';
        $venue = $this->getRelationship();
        $this->assertDefaultHasMany($venue);

        try
        {
            $venue->events[0]->description;
            $this->fail('expected Exception ExceptionUndefinedProperty');
        }
        catch (ExceptionUndefinedProperty $e)
        {
            $this->assertTrue(\strpos($e->getMessage(), 'description') !== false);
        }
    }

    public function testHasManyWithReadOnly()
    {
        Venue::$has_many[0]['readonly'] = true;
        $venue = $this->getRelationship();
        $this->assertDefaultHasMany($venue);

        try
        {
            $venue->events[0]->save();
            $this->fail('expected exception ExceptionReadOnly');
        }
        catch (ExceptionReadOnly $e)
        {

        }

        $venue->events[0]->description = 'new desc';
        $this->assertEquals($venue->events[0]->description, 'new desc');
    }

    public function testHasManyWithSingularAttributeName()
    {
        Venue::$has_many = [[
        'event',
        'class_name' => 'Event',
        'order' => 'id asc']];
        $this->assertDefaultHasMany($this->getRelationship(), 'event');
    }

    public function testHasManyWithConditionsAndNonQualifyingRecord()
    {
        Venue::$has_many[0]['conditions'] = "title = 'pr0n @ railsconf'";
        $venue = $this->getRelationship();
        $this->assertEquals(2, $venue->id);
        $this->assertTrue(empty($venue->events), \is_array($venue->events));
    }

    public function testHasManyWithConditionsAndQualifyingRecord()
    {
        Venue::$has_many[0]['conditions'] = "title = 'Yeah Yeah Yeahs'";
        $venue = $this->getRelationship();
        $this->assertEquals(2, $venue->id);
        $this->assertEquals($venue->events[0]->title, 'Yeah Yeah Yeahs');
    }

    public function testHasManyWithSqlClauseOptions()
    {
        Venue::$has_many[0] = ['events',
            'select' => 'type',
            'group' => 'type',
            'limit' => 2,
            'offset' => 1];
        Venue::first()->events;
        $this->assertSqlHas($this->conn->limit("SELECT type FROM events WHERE venue_id=? GROUP BY type",
                        1, 2), Event::table()->last_sql);
    }

    public function testHasManyThrough()
    {
        $hosts = Venue::find(2)->hosts;
        $this->assertEquals(2, $hosts[0]->id);
        $this->assertEquals(3, $hosts[1]->id);
    }

    public function testGh27HasManyThroughWithExplicitKeys()
    {
        $property = Property::first();
        $this->assertEquals(1, $property->amenities[0]->amenity_id);
        $this->assertEquals(2, $property->amenities[1]->amenity_id);
    }

    public function testGh16HasManyThroughInsideALoopShouldNotCauseAnException()
    {
        $count = 0;

        foreach (Venue::all() as $venue)
        {
            $count += \count($venue->hosts);
        }

        $this->assertTrue($count >= 5);
    }

    /**
     * @expectedException Activerecord\HasManyThroughAssociationException
     */
    public function testHasManyThroughNoAssociation()
    {
        Event::$belongs_to = [[
        'host']];
        Venue::$has_many[1] = ['hosts',
            'through' => 'blahhhhhhh'];

        $venue = $this->getRelationship();
        $n = $venue->hosts;
        $this->assertTrue(\count($n) > 0);
    }

    public function testHasManyThroughWithSelect()
    {
        Event::$belongs_to = [[
        'host']];
        Venue::$has_many[1] = ['hosts',
            'through' => 'events',
            'select' => 'hosts.*, events.*'];

        $venue = $this->getRelationship();
        $this->assertTrue(\count($venue->hosts) > 0);
        $this->assertNotNull($venue->hosts[0]->title);
    }

    public function testHasManyThroughWithConditions()
    {
        Event::$belongs_to = [[
        'host']];
        Venue::$has_many[1] = ['hosts',
            'through' => 'events',
            'conditions' => [
                'events.title != ?',
                'Love Overboard']];

        $venue = $this->getRelationship();
        $this->assertTrue(\count($venue->hosts) === 1);
        $this->assertSqlHas("events.title !=", Table::load('Host')->last_sql);
    }

    public function testHasManyThroughUsingSource()
    {
        Event::$belongs_to = [[
        'host']];
        Venue::$has_many[1] = ['hostess',
            'through' => 'events',
            'source' => 'host'];

        $venue = $this->getRelationship();
        $this->assertTrue(\count($venue->hostess) > 0);
    }

    /**
     * @expectedException ReflectionException
     */
    public function testHasManyThroughWithInvalidClassName()
    {
        Event::$belongs_to = [[
        'host']];
        Venue::$has_one = [[
        'invalid_assoc']];
        Venue::$has_many[1] = ['hosts',
            'through' => 'invalid_assoc'];
        $this->getRelationship()->hosts;
    }

    public function testHasManyWithJoins()
    {
        $x = Venue::first(['joins' => ['events']]);
        $this->assertSqlHas('INNER JOIN events ON(venues.id = events.venue_id)',
                Venue::table()->last_sql);
    }

    public function testHasManyWithExplicitKeys()
    {
        $old = Author::$has_many;
        Author::$has_many = [[
        'explicit_books',
        'class_name' => 'Book',
        'primary_key' => 'parent_author_id',
        'foreign_key' => 'secondary_author_id']];
        $author = Author::find(4);

        foreach ($author->explicit_books as $book)
        {
            $this->assertEquals($book->secondary_author_id,
                    $author->parent_author_id);
        }

        $this->assertTrue(\strpos(Table::load('Book')->last_sql,
                        "secondary_author_id") !== false);
        Author::$has_many = $old;
    }

    public function testGh93AndGh100EagerLoadingRespectsAssociationOptions()
    {
        Venue::$has_many = [[
        'events',
        'class_name' => 'Event',
        'order' => 'id asc',
        'conditions' => [
            'length(title) = ?',
            14]]];
        $venues = Venue::find([2,
                    6], ['include' => 'events']);

        $this->assertSqlHas("WHERE length(title) = ? AND venue_id IN(?,?) ORDER BY id asc",
                Table::load('Event')->last_sql);
        $this->assertEquals(1, count($venues[0]->events));
    }

    public function testEagerLoadingHasManyX()
    {
        $venues = Venue::find([2,
                    6], ['include' => 'events']);
        $this->assertSqlHas("WHERE venue_id IN(?,?)",
                Table::load('Event')->last_sql);

        foreach ($venues[0]->events as $event)
        {
            $this->assertEquals($event->venue_id, $venues[0]->id);
        }

        $this->assertEquals(2, \count($venues[0]->events));
    }

    public function testEagerLoadingHasManyWithNoRelatedRows()
    {
        $venues = Venue::find([7,
                    8], [
                    'include' => 'events']);

        foreach ($venues as $v)
        {
            $this->assertTrue(empty($v->events));
        }

        $this->assertSqlHas("WHERE id IN(?,?)", Table::load('Venue')->last_sql);
        $this->assertSqlHas("WHERE venue_id IN(?,?)",
                Table::load('Event')->last_sql);
    }

    public function testEagerLoadingHasManyArrayOfIncludes()
    {
        Author::$has_many = [[
        'books'],
            ['awesome_people']];
        $authors = Author::find([1,
                    2],
                        ['include' => ['books',
                        'awesome_people']]);

        $assocs = ['books',
            'awesome_people'];

        foreach ($assocs as $assoc)
        {
            $this->assertInternalType('array', $authors[0]->$assoc);

            foreach ($authors[0]->$assoc as $a)
            {
                $this->assertEquals($authors[0]->author_id, $a->author_id);
            }
        }

        foreach ($assocs as $assoc)
        {
            $this->assertInternalType('array', $authors[1]->$assoc);
            $this->assertTrue(empty($authors[1]->$assoc));
        }

        $this->assertSqlHas("WHERE author_id IN(?,?)",
                Table::load('Author')->last_sql);
        $this->assertSqlHas("WHERE author_id IN(?,?)",
                Table::load('Book')->last_sql);
        $this->assertSqlHas("WHERE author_id IN(?,?)",
                Table::load('AwesomePerson')->last_sql);
    }

    public function testEagerLoadingHasManyNested()
    {
        $venues = Venue::find([1,
                    2], ['include' => ['events' => ['host']]]);
        $this->assertEquals(2, \count($venues));

        foreach ($venues as $v)
        {
            $this->assertTrue(\count($v->events) > 0);

            foreach ($v->events as $e)
            {
                $this->assertEquals($e->host_id, $e->host->id);
                $this->assertEquals($v->id, $e->venue_id);
            }
        }

        $this->assertSqlHas("WHERE id IN(?,?)", Table::load('Venue')->last_sql);
        $this->assertSqlHas("WHERE venue_id IN(?,?)",
                Table::load('Event')->last_sql);
        $this->assertSqlHas("WHERE id IN(?,?,?)", Table::load('Host')->last_sql);
    }

}