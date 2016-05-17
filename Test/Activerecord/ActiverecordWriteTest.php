<?php

namespace Test\Activerecord;

use Activerecord\DateTime;
use Activerecord\Exceptions\ExceptionDatabase;
use Activerecord\Adapters\Oci;

class ActiverecordWriteTest
        extends \Test\Helpers\DatabaseTest
{

    private function makeNewBookAnd($save = true)
    {
        $book = new Book();
        $book->name = 'rivers cuomo';
        $book->special = 1;

        if ($save)
        {
            $book->save();
        }

        return $book;
    }

    public function testSave()
    {
        $venue = new Venue(['name' => 'Tito']);
        $venue->save();
    }

    public function testInsert()
    {
        $author = new Author(['name' => 'Blah Blah']);
        $author->save();
        $this->assertNotNull(Author::find($author->id));
    }

    /**
     * @expectedException Activerecord\DatabaseException
     */
    public function testInsertWithNoSequenceDefined()
    {
        if (!$this->conn->supportsSequences()) throw new ExceptionDatabase('');

        AuthorWithoutSequence::create(['name' => 'Bob!']);
    }

    public function testInsertShouldQuoteKeys()
    {
        $author = new Author(['name' => 'Blah Blah']);
        $author->save();
        $this->assertTrue(\strpos($author->connection()->lastQuery,
                        $author->connection()->quoteName('updated_at')) !== false);
    }

    public function testSaveAutoIncrementId()
    {
        $venue = new Venue(['name' => 'Bob']);
        $venue->save();
        $this->assertTrue($venue->id > 0);
    }

    public function testSequenceWasSet()
    {
        if ($this->conn->supportsSequences()) $this->assertEquals($this->conn->getSequenceName('authors',
                            'author_id'), Author::table()->sequence);
        else $this->assertNull(Author::table()->sequence);
    }

    public function testSequenceWasExplicitlySet()
    {
        if ($this->conn->supportsSequences()) $this->assertEquals(AuthorExplicitSequence::$sequence,
                    AuthorExplicitSequence::table()->sequence);
        else $this->assertNull(Author::table()->sequence);
    }

    public function testDelete()
    {
        $author = Author::find(1);
        $author->delete();

        $this->assertFalse(Author::exists(1));
    }

    public function testDeleteByFindAll()
    {
        $books = Book::all();

        foreach ($books as $model)
        {
            $model->delete();
        }

        $res = Book::all();
        $this->assertEquals(0, count($res));
    }

    public function testUpdate()
    {
        $book = Book::find(1);
        $new_name = 'new name';
        $book->name = $new_name;
        $book->save();

        $this->assertSame($new_name, $book->name);
        $this->assertSame($new_name, $book->name, Book::find(1)->name);
    }

    public function testUpdateShouldQuoteKeys()
    {
        $book = Book::find(1);
        $book->name = 'new name';
        $book->save();
        $this->assertTrue(\strpos($book->connection()->last_query,
                        $book->connection()->quoteName('name')) !== false);
    }

    public function testUpdateAttributes()
    {
        $book = Book::find(1);
        $new_name = 'How to lose friends and alienate people'; // jax i'm worried about you
        $attrs = ['name' => $new_name];
        $book->updateAttributes($attrs);

        $this->assertSame($new_name, $book->name);
        $this->assertSame($new_name, $book->name, Book::find(1)->name);
    }

    /**
     * @expectedException Activerecord\UndefinedPropertyException
     */
    public function testUpdateAttributesUndefinedProperty()
    {
        $book = Book::find(1);
        $book->update_attributes(array(
            'name' => 'new name',
            'invalid_attribute' => true,
            'another_invalid_attribute' => 'blah'));
    }

    public function testUpdateAttribute()
    {
        $book = Book::find(1);
        $new_name = 'some stupid self-help book';
        $book->updateAttribute('name', $new_name);

        $this->assertSame($new_name, $book->name);
        $this->assertSame($new_name, $book->name, Book::find(1)->name);
    }

    /**
     * @expectedException Activerecord\UndefinedPropertyException
     */
    public function testUpdateAttributeUndefinedProperty()
    {
        $book = Book::find(1);
        $book->updateAttribute('invalid_attribute', true);
    }

    public function testSaveNullValue()
    {
        $book = Book::first();
        $book->name = null;
        $book->save();
        $this->assertSame(null, Book::find($book->id)->name);
    }

    public function testSaveBlankValue()
    {
        // oracle doesn't do blanks. probably an option to enable?
        if ($this->conn instanceof Oci) return;

        $book = Book::find(1);
        $book->name = '';
        $book->save();
        $this->assertSame('', Book::find(1)->name);
    }

    public function testDirtyAttributes()
    {
        $book = $this->make_new_book_and(false);
        $this->assertEquals(['name',
            'special'], \array_keys($book->dirtyAttributes()));
    }

    public function testDirtyAttributesClearedAfterSaving()
    {
        $book = $this->make_new_book_and();
        $this->assertTrue(\strpos($book->table()->last_sql, 'name') !== false);
        $this->assertTrue(\strpos($book->table()->last_sql, 'special') !== false);
        $this->assertEquals(null, $book->dirtyAttributes());
    }

    public function testDirtyAttributesClearedAfterInserting()
    {
        $book = $this->make_new_book_and();
        $this->assertEquals(null, $book->dirtyAttributes());
    }

    public function testNoDirtyAttributesButStillInsertRecord()
    {
        $book = new Book;
        $this->assertEquals(null, $book->dirtyAttributes());
        $book->save();
        $this->assertEquals(null, $book->dirtyAttributes());
        $this->assertNotNull($book->id);
    }

    public function testDirtyAttributesClearedAfterUpdating()
    {
        $book = Book::first();
        $book->name = 'rivers cuomo';
        $book->save();
        $this->assertEquals(null, $book->dirtyAttributes());
    }

    public function testDirtyAttributesAfterReloading()
    {
        $book = Book::first();
        $book->name = 'rivers cuomo';
        $book->reload();
        $this->assertEquals(null, $book->dirtyAttributes());
    }

    public function testDirtyAttributesWithMassAssignment()
    {
        $book = Book::first();
        $book->setAttributes(array(
            'name' => 'rivers cuomo'));
        $this->assertEquals(array(
            'name'), array_keys($book->dirtyAttributes()));
    }

    public function test_timestamps_set_before_save()
    {
        $author = new Author;
        $author->save();
        $this->assertNotNull($author->created_at, $author->updated_at);

        $author->reload();
        $this->assertNotNull($author->created_at, $author->updated_at);
    }

    public function testTimestampsUpdatedAtOnlySetBeforeUpdate()
    {
        $author = new Author();
        $author->save();
        $created_at = $author->created_at;
        $updated_at = $author->updated_at;
        sleep(1);

        $author->name = 'test';
        $author->save();

        $this->assertNotNull($author->updated_at);
        $this->assertSame($created_at, $author->created_at);
        $this->assertNotEquals($updated_at, $author->updated_at);
    }

    public function testCreate()
    {
        $author = Author::create(['name' => 'Blah Blah']);
        $this->assertNotNull(Author::find($author->id));
    }

    public function testCreateShouldSetCreatedAt()
    {
        $author = Author::create(['name' => 'Blah Blah']);
        $this->assertNotNull($author->created_at);
    }

    /**
     * @expectedException Activerecord\ActiverecordException
     */
    public function testUpdateWithNoPrimaryKeyDefined()
    {
        Author::table()->pk = [];
        $author = Author::first();
        $author->name = 'blahhhhhhhhhh';
        $author->save();
    }

    /**
     * @expectedException Activerecord\ActiverecordException
     */
    public function testDeleteWithNoPrimaryKeyDefined()
    {
        Author::table()->pk = [];
        $author = author::first();
        $author->delete();
    }

    public function testInsertingWithExplicitPk()
    {
        $author = Author::create([
                    'author_id' => 9999,
                    'name' => 'blah']);
        $this->assertEquals(9999, $author->author_id);
    }

    /**
     * @expectedException Activerecord\ReadOnlyException
     */
    public function testReadonly()
    {
        $author = Author::first(['readonly' => true]);
        $author->save();
    }

    public function testModifiedAttributesInBeforeHandlersGetSaved()
    {
        $author = DirtyAuthor::first();
        $author->encrypted_password = 'coco';
        $author->save();
        $this->assertEquals('i saved', DirtyAuthor::find($author->id)->name);
    }

    public function testIsDirty()
    {
        $author = Author::first();
        $this->assertEquals(false, $author->isDirty());

        $author->name = 'coco';
        $this->assertEquals(true, $author->isDirty());
    }

    public function testSetDateFlagsDirty()
    {
        $author = Author::create(['some_date' => new DateTime()]);
        $author = Author::find($author->id);
        $author->some_date->setDate(2010, 1, 1);
        $this->assertHasKeys('some_date', $author->dirtyAttributes());
    }

    public function testSetDateFlagsDirtyWithPhpDatetime()
    {
        $author = Author::create(['some_date' => new \DateTime()]);
        $author = Author::find($author->id);
        $author->some_date->setDate(2010, 1, 1);
        $this->assertHasKeys('some_date', $author->dirtyAttributes());
    }

    public function testDeleteAllWithConditionsAsString()
    {
        $num_affected = Author::deleteAll(['conditions' => 'parent_author_id = 2']);
        $this->assertEquals(2, $num_affected);
    }

    public function testDeleteAllWithConditionsAsHash()
    {
        $num_affected = Author::deleteAll(['conditions' => ['parent_author_id' => 2]]);
        $this->assertEquals(2, $num_affected);
    }

    public function testDeleteAllWithConditionsAsArray()
    {
        $num_affected = Author::deleteAll(['conditions' => ['parent_author_id = ?',
                        2]]);
        $this->assertEquals(2, $num_affected);
    }

    public function testDeleteAllWithLimitAndOrder()
    {
        if (!$this->conn->acceptsLimitAndOrderForUpdateAndDelete()) $this->markTestSkipped('Only MySQL & Sqlite accept limit/order with UPDATE clause');

        $num_affected = Author::deleteAll(['conditions' => ['parent_author_id = ?',
                        2],
                    'limit' => 1,
                    'order' => 'name asc']);
        $this->assertEquals(1, $num_affected);
        $this->assertTrue(\strpos(Author::table()->last_sql,
                        'ORDER BY name asc LIMIT 1') !== false);
    }

    public function testUpdateAllWithSetAsString()
    {
        $num_affected = Author::updateAll(['set' => 'parent_author_id = 2']);
        $this->assertEquals(2, $num_affected);
        $this->assertEquals(4, Author::count_by_parent_author_id(2));
    }

    public function testUpdateAllWithSetAsHash()
    {
        $num_affected = Author::updateAll(['set' => ['parent_author_id' => 2]]);
        $this->assertEquals(2, $num_affected);
    }

    /**
     * TODO: not implemented
      public function test_update_all_with_set_as_array()
      {
      $num_affected = Author::update_all(array('set' => array('parent_author_id = ?', 2)));
      $this->assertEquals(2, $num_affected);
      }
     */
    public function testUpdateAllWithConditionsAsString()
    {
        $num_affected = Author::updateAll(['set' => 'parent_author_id = 2',
                    'conditions' => 'name = "Tito"']);
        $this->assertEquals(1, $num_affected);
    }

    public function testUpdateAllWithConditionsAsHash()
    {
        $num_affected = Author::updateAll(['set' => 'parent_author_id = 2',
                    'conditions' => ['name' => "Tito"]]);
        $this->assertEquals(1, $num_affected);
    }

    public function testUpdateAllWithConditionsAsArray()
    {
        $num_affected = Author::updateAll(['set' => 'parent_author_id = 2',
                    'conditions' => ['name = ?',
                        "Tito"]]);
        $this->assertEquals(1, $num_affected);
    }

    public function testUpdateAllWithLimitAndOrder()
    {
        if (!$this->conn->acceptsLimitAndOrderForUpdateAndDelete())
        {
            $this->markTestSkipped('Only MySQL & Sqlite accept limit/order with UPDATE clause');
        }

        $num_affected = Author::updateAll([
                    'set' => 'parent_author_id = 2',
                    'limit' => 1,
                    'order' => 'name asc']);
        $this->assertEquals(1, $num_affected);
        $this->assertTrue(\strpos(Author::table()->last_sql,
                        'ORDER BY name asc LIMIT 1') !== false);
    }

    public function testUpdateNativeDatetime()
    {
        $author = Author::create(['name' => 'Blah Blah']);
        $native_datetime = new \DateTime('1983-12-05');
        $author->some_date = $native_datetime;
        $this->assertFalse($native_datetime === $author->some_date);
    }

    public function testUpdateOurDatetime()
    {
        $author = Author::create([
                    'name' => 'Blah Blah']);
        $our_datetime = new DateTime('1983-12-05');
        $author->some_date = $our_datetime;
        $this->assertTrue($our_datetime === $author->some_date);
    }

}