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
 * Description of AbstractRelationsTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class AbstractRelationsTest
        extends DatabaseTest
{

    public function setUp()
    {
        parent::setUp($connection_name);
    }

    public function tearDown()
    {

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

    public function testBuildAssociationOverwritesGuardedForeignKeys()
    {
        $author = new AuthorAttrAccessible();
        $author->save();

        $book = $author->build_book();

        $this->assertNotNull($book->author_id);
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

    /**
     * @expectedException Activerecord\RelationshipException
     */
    public function testThrowErrorIfRelationshipIsNotAModel()
    {
        AuthorWithNonModelRelationship::first()->books;
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