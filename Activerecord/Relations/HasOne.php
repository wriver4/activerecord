<?php

namespace Activerecord\Relations;

use Activerecord\Relations\AbstractRelations;

/**
 * Summary of file HasOne.
 *
 * Description of file HasOne.
 *
 *
 * @license
 *
 * @copyright
 *
 * @version
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */

/**
 * One-to-one relationship.
 *
 * <code>
 * # Table name: states
 * # Primary key: id
 * class State extends Activerecord\Model {}
 *
 * # Table name: people
 * # Foreign key: state_id
 * class Person extends Activerecord\Model {
 *   static $has_one = array(array('state'));
 * }
 * </code>
 *
 * @package Activerecord
 * @see http://www.phpActiverecord.org/guides/associations
 */
class HasOne
        extends AbstractRelations
{

    public function __construct($options = [])
    {

    }

    public function load(Model $model)
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

            case 'has_one';
                $ret = Employee::find(1);
                break;

            case 'has_many';
                $ret = Venue::find(2);
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

    protected function assertDefaultHasMany($venue, $association_name = 'events')
    {
        $this->assertEquals(2, $venue->id);
        $this->assertTrue(count($venue->$association_name) > 1);
        $this->assertEquals('Yeah Yeah Yeahs',
                $venue->{$association_name}[0]->title);
    }

    protected function assertDefaultHasOne($employee,
            $association_name = 'position')
    {
        $this->assertTrue($employee->$association_name instanceof Position);
        $this->assertEquals('physicist', $employee->$association_name->title);
        $this->assertNotNull($employee->id, $employee->$association_name->title);
    }

    public function testHasManyBasic()
    {
        $this->assertDefaultHasMany($this->getRelationship());
    }

    public function testGh256EagerLoadingThreeLevelsDeep()
    {
        /* Before fix Undefined offset: 0 */
        $conditions['include'] = array(
            'events' => array(
                'host' => array(
                    'events')));
        $venue = Venue::find(2, $conditions);

        $events = $venue->events;
        $this->assertEquals(2, count($events));
        $event_yeah_yeahs = $events[0];
        $this->assertEquals('Yeah Yeah Yeahs', $event_yeah_yeahs->title);

        $event_host = $event_yeah_yeahs->host;
        $this->assertEquals('Billy Crystal', $event_host->name);

        $bill_events = $event_host->events;

        $this->assertEquals('Yeah Yeah Yeahs', $bill_events[0]->title);
    }

    /**
     * @expectedException Activerecord\RelationshipException
     */
    public function testJoinsOnModelViaUndeclaredAssociation()
    {
        $x = JoinBook::first(['joins' => ['undeclared']]);
    }

    public function testJoinsOnlyLoadsGivenModelAttributes()
    {
        $x = Event::first(['joins' => ['venue']]);
        $this->assertSqlHas('SELECT events.*', Event::table()->last_sql);
        $this->assertFalse(\array_key_exists('city', $x->attributes()));
    }

    public function testJoinsCombinedWithSelectLoadsAllAttributes()
    {
        $x = Event::first(['select' => 'events.*, venues.city as venue_city',
                    'joins' => ['venue']]);
        $this->assertSqlHas('SELECT events.*, venues.city as venue_city',
                Event::table()->last_sql);
        $this->assertTrue(\array_key_exists('venue_city', $x->attributes()));
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

    public function testHasManyBuildAssociation()
    {
        $author = Author::first();
        $this->assertEquals($author->id, $author->build_books()->author_id);
        $this->assertEquals($author->id, $author->build_book()->author_id);
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

    public function testBuildAssociationOverwritesGuardedForeignKeys()
    {
        $author = new AuthorAttrAccessible();
        $author->save();

        $book = $author->build_book();

        $this->assertNotNull($book->author_id);
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

    public function testDoNotAttemptToLoadIfAllForeignKeysAreNull()
    {
        $event = new Event();
        $event->venue;
        $this->assertSqlDoesNotHas($this->conn->last_query, 'is IS NULL');
    }

    public function testRelationshipOnTableWithUnderscores()
    {
        $this->assertEquals(1, Author::find(1)->awesome_person->is_awesome);
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

    /**
     * @expectedException Activerecord\RelationshipException
     */
    public function testThrowErrorIfRelationshipIsNotAModel()
    {
        AuthorWithNonModelRelationship::first()->books;
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

    public function testEagerLoadingClonesRelatedObjects()
    {
        $events = Event::find([2,
                    3], ['include' => ['venue']]);

        $venue = $events[0]->venue;
        $venue->name = "new name";

        $this->assertEquals($venue->id, $events[1]->venue->id);
        $this->assertNotEquals($venue->name, $events[1]->venue->name);
        $this->assertNotEquals(\spl_object_hash($venue),
                \spl_object_hash($events[1]->venue));
    }

    public function testEagerLoadingClonesNestedRelatedObjects()
    {
        $venues = Venue::find([1,
                    2,
                    6,
                    9], ['include' => ['events' => ['host']]]);

        $unchanged_host = $venues[2]->events[0]->host;
        $changed_host = $venues[3]->events[0]->host;
        $changed_host->name = "changed";

        $this->assertEquals($changed_host->id, $unchanged_host->id);
        $this->assertNotEquals($changed_host->name, $unchanged_host->name);
        $this->assertNotEquals(\spl_object_hash($changed_host),
                \spl_object_hash($unchanged_host));
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

    public function testGh40RelationshipsWithJoinsAliasesTableNameInConditions()
    {
        $event = Event::find(1, ['joins' => ['venue']]);

        $this->assertEquals($event->id, $event->venue->id);
    }

    /**
     * @expectedException Activerecord\RecordNotFound
     */
    public function testDoNotAttemptEagerLoadWhenRecordDoesNotExist()
    {
        Author::find(999999, ['include' => ['books']]);
    }

}