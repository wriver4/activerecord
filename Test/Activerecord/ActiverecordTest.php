<?php

namespace Test\Activerecord;

use Activerecord\Table;
use Activerecord\Exceptions\ExceptionReadOnly;

class ActiverecordTest
        extends \Test\Helpers\DatabaseTest
{

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
        $this->options = ['conditions' => 'blah',
            'order' => 'blah'];
    }

    public function testOptionsIsNot()
    {
        $this->assertFalse(Author::isOptionsHash(null));
        $this->assertFalse(Author::isOptionsHash(''));
        $this->assertFalse(Author::isOptionsHash('tito'));
        $this->assertFalse(Author::isOptionsHash([]));
        $this->assertFalse(Author::isOptionsHash([1,
                    2,
                    3]));
    }

    /**
     * @expectedException Activerecord\ActiverecordException
     */
    public function testOptionsHashWithUnknownKeys()
    {
        $this->assertFalse(Author::isOptionsHash([
                    'conditions' => 'blah',
                    'sharks' => 'laserz',
                    'dubya' => 'bush']));
    }

    public function testOptionsIsHash()
    {
        $this->assertTrue(Author::isOptionsHash($this->options));
    }

    public function testExtractAndValidateOptions()
    {
        $args = ['first',
            $this->options];
        $this->assertEquals($this->options,
                Author::extractAndValidateOptions($args));
        $this->assertEquals(['first'], $args);
    }

    public function testExtractAndValidateOptionsWithArrayInArgs()
    {
        $args = ['first',
            [1,
                2],
            $this->options];
        $this->assertEquals($this->options,
                Author::extractAndValidateOptions($args));
    }

    public function testExtractAndValidateOptionsRemovesOptionsHash()
    {
        $args = ['first',
            $this->options];
        Author::extractAndValidateOptions($args);
        $this->assertEquals(['first'], $args);
    }

    public function testExtractAndValidateOptionsNope()
    {
        $args = ['first'];
        $this->assertEquals([], Author::extractAndValidateOptions($args));
        $this->assertEquals(['first'], $args);
    }

    public function testExtractAndValidateOptionsNopeBecauseWasNotAtEnd()
    {
        $args = ['first',
            $this->options,
            [1,
                2]];
        $this->assertEquals([], Author::extractAndValidateOptions($args));
    }

    /**
     * @expectedException Activerecord\UndefinedPropertyException
     */
    public function testInvalidAttribute()
    {
        $author = Author::find('first', ['conditions' => 'author_id=1']);
        $author->some_invalid_field_name;
    }

    public function testInvalidAttributes()
    {
        $book = Book::find(1);
        try
        {
            $book->update_attributes([
                'name' => 'new name',
                'invalid_attribute' => true,
                'another_invalid_attribute' => 'something']);
        }
        catch (ExceptionUndefinedProperty $e)
        {
            $exceptions = \explode("\r\n", $e->getMessage());
        }

        $this->assertEquals(1,
                \substr_count($exceptions[0], 'invalid_attribute'));
        $this->assertEquals(1,
                \substr_count($exceptions[1], 'another_invalid_attribute'));
    }

    public function testGetterUndefinedPropertyExceptionIncludesModelName()
    {
        $this->assertExceptionMessageContains("Author->this_better_not_exist",
                function()
        {
            $author = new Author();
            $author->this_better_not_exist;
        });
    }

    public function testMassAssignmentUndefinedPropertyExceptionIncludesModelName()
    {
        $this->assertExceptionMessageContains("Author->this_better_not_exist",
                function()
        {
            new Author(["this_better_not_exist" => "hi"]);
        });
    }

    public function testSetterUndefinedPropertyExceptionIncludesModelName()
    {
        $this->assertExceptionMessageContains("Author->this_better_not_exist",
                function()
        {
            $author = new Author();
            $author->this_better_not_exist = "hi";
        });
    }

    public function testGetValuesFor()
    {
        $book = Book::find_by_name('Ancient Art of Main Tanking');
        $ret = $book->getValuesFor([
            'book_id',
            'author_id']);
        $this->assertEquals([
            'book_id',
            'author_id'], \array_keys($ret));
        $this->assertEquals([1,
            1], \array_values($ret));
    }

    public function testHyphenatedColumnNamesToUnderscore()
    {
        if ($this->conn instanceof Oci)
        {
            return;
        }

        $keys = \array_keys(RmBldg::first()->attributes());
        $this->assertTrue(\in_array('rm_name', $keys));
    }

    public function testColumnNamesWithSpaces()
    {
        if ($this->conn instanceof Oci)
        {
            return;
        }

        $keys = \array_keys(RmBldg::first()->attributes());
        $this->assertTrue(\in_array('space_out', $keys));
    }

    public function testMixedCaseColumnName()
    {
        $keys = \array_keys(Author::first()->attributes());
        $this->assertTrue(\in_array('mixedcasefield', $keys));
    }

    public function testMixedCasePrimaryKeySave()
    {
        $venue = Venue::find(1);
        $venue->name = 'should not throw exception';
        $venue->save();
        $this->assertEquals($venue->name, Venue::find(1)->name);
    }

    public function testReload()
    {
        $venue = Venue::find(1);
        $this->assertEquals('NY', $venue->state);
        $venue->state = 'VA';
        $this->assertEquals('VA', $venue->state);
        $venue->reload();
        $this->assertEquals('NY', $venue->state);
    }

    public function testReloadProtectedAttribute()
    {
        $book = BookAttrAccessible::find(1);

        $book->name = "Should not stay";
        $book->reload();
        $this->assertNotEquals("Should not stay", $book->name);
    }

    public function testActiveRecordModelHomeNotSet()
    {
        $home = Config::instance()->getModelDirectory();
        Config::instance()->setModelDirectory(__FILE__);
        $this->assertEquals(false, \class_exists('TestAutoload'));

        Config::instance()->setModelDirectory($home);
    }

    public function testAutoLoadWithNamespacedModel()
    {
        $this->assertTrue(\class_exists('NamespaceTest\Book'));
    }

    public function testNamespaceGetsStrippedFromTableName()
    {
        $model = new NamespaceTest\Book();
        $this->assertEquals('books', $model->table()->table);
    }

    public function testNamespaceGetsStrippedFromInferredForeignKey()
    {
        $model = new NamespaceTest\Book();
        $table = Table::load(\get_class($model));

        $this->assertEquals($table->getRelationship('parent_book')->foreign_key[0],
                'book_id');
        $this->assertEquals($table->getRelationship('parent_book_2')->foreign_key[0],
                'book_id');
        $this->assertEquals($table->getRelationship('parent_book_3')->foreign_key[0],
                'book_id');
    }

    public function testNamespacedRelationshipAssociatesCorrectly()
    {
        $model = new NamespaceTest\Book();
        $table = Table::load(\get_class($model));

        $this->assertNotNull($table->getRelationship('parent_book'));
        $this->assertNotNull($table->getRelationship('parent_book_2'));
        $this->assertNotNull($table->getRelationship('parent_book_3'));

        $this->assertNotNull($table->getRelationship('pages'));
        $this->assertNotNull($table->getRelationship('pages_2'));

        $this->assertNull($table->getRelationship('parent_book_4'));
        $this->assertNull($table->getRelationship('pages_3'));

        // Should refer to the same class
        $this->assertSame(
                ltrim($table->getRelationship('parent_book')->class_name, '\\'),
                ltrim($table->getRelationship('parent_book_2')->class_name, '\\')
        );

        // Should refer to different classes
        $this->assertNotSame(
                ltrim($table->getRelationship('parent_book_2')->class_name, '\\'),
                ltrim($table->getRelationship('parent_book_3')->class_name, '\\')
        );

        // Should refer to the same class
        $this->assertSame(
                ltrim($table->getRelationship('pages')->class_name, '\\'),
                ltrim($table->getRelationship('pages_2')->class_name, '\\')
        );
    }

    public function testShouldHaveAllColumnAttributesWhenInitializingWithArray()
    {
        $author = new Author(['name' => 'Tito']);
        $this->assertTrue(\count(\array_keys($author->attributes())) >= 9);
    }

    public function testDefaults()
    {
        $author = new Author();
        $this->assertEquals('default_name', $author->name);
    }

    public function testAliasAttributeGetter()
    {
        $venue = Venue::find(1);
        $this->assertEquals($venue->marquee, $venue->name);
        $this->assertEquals($venue->mycity, $venue->city);
    }

    public function testAliasAttributeSetter()
    {
        $venue = Venue::find(1);
        $venue->marquee = 'new name';
        $this->assertEquals($venue->marquee, 'new name');
        $this->assertEquals($venue->marquee, $venue->name);

        $venue->name = 'another name';
        $this->assertEquals($venue->name, 'another name');
        $this->assertEquals($venue->marquee, $venue->name);
    }

    public function testAliasFromMassAttributes()
    {
        $venue = new Venue(['marquee' => 'meme',
            'id' => 123]);
        $this->assertEquals('meme', $venue->name);
        $this->assertEquals($venue->marquee, $venue->name);
    }

    public function testGh18IssetOnAliasedAttribute()
    {
        $this->assertTrue(isset(Venue::first()->marquee));
    }

    public function testAttrAccessible()
    {
        $book = new BookAttrAccessible(array(
            'name' => 'should not be set',
            'author_id' => 1));
        $this->assertNull($book->name);
        $this->assertEquals(1, $book->author_id);
        $book->name = 'test';
        $this->assertEquals('test', $book->name);
    }

    public function testAttrProtected()
    {
        $book = new BookAttrAccessible(array(
            'book_id' => 999));
        $this->assertNull($book->book_id);
        $book->book_id = 999;
        $this->assertEquals(999, $book->book_id);
    }

    public function testIsset()
    {
        $book = new Book();
        $this->assertTrue(isset($book->name));
        $this->assertFalse(isset($book->sharks));
    }

    public function testReadonlyOnlyHaltOnWriteMethod()
    {
        $book = Book::first(['readonly' => true]);
        $this->assertTrue($book->isReadonly());

        try
        {
            $book->save();
            $this->fail('expected exception ExceptionReadonly');
        }
        catch (ExceptionReadonly $e)
        {

        }

        $book->name = 'some new name';
        $this->assertEquals($book->name, 'some new name');
    }

    public function testCastWhenUsingSetter()
    {
        $book = new Book();
        $book->book_id = '1';
        $this->assertSame(1, $book->book_id);
    }

    public function testCastWhenLoading()
    {
        $book = Book::find(1);
        $this->assertSame(1, $book->book_id);
        $this->assertSame('Ancient Art of Main Tanking', $book->name);
    }

    public function testCastDefaults()
    {
        $book = new Book();
        $this->assertSame(0.0, $book->special);
    }

    public function testTransactionCommitted()
    {
        $original = Author::count();
        $ret = Author::transaction(function()
                {
                    Author::create(["name" => "blah"]);
                });
        $this->assertEquals($original + 1, Author::count());
        $this->assertTrue($ret);
    }

    public function testTransactionCommittedWhenReturningTrue()
    {
        $original = Author::count();
        $ret = Author::transaction(function()
                {
                    Author::create(["name" => "blah"]);
                    return true;
                });
        $this->assertEquals($original + 1, Author::count());
        $this->assertTrue($ret);
    }

    public function testTransactionRolledbackByReturningFalse()
    {
        $original = Author::count();

        $ret = Author::transaction(function()
                {
                    Author::create(["name" => "blah"]);
                    return false;
                });

        $this->assertEquals($original, Author::count());
        $this->assertFalse($ret);
    }

    public function testTransactionRolledbackByThrowingException()
    {
        $original = Author::count();
        $exception = null;

        try
        {
            Author::transaction(function()
            {
                Author::create(["name" => "blah"]);
                throw new Exception("blah");
            });
        }
        catch (Exception $e)
        {
            $exception = $e;
        }

        $this->assertNotNull($exception);
        $this->assertEquals($original, Author::count());
    }

    public function testDelegate()
    {
        $event = Event::first();
        $this->assertEquals($event->venue->state, $event->state);
        $this->assertEquals($event->venue->address, $event->address);
    }

    public function testDelegatePrefix()
    {
        $event = Event::first();
        $this->assertEquals($event->host->name, $event->woot_name);
    }

    public function testDelegateReturnsNullIfRelationshipDoesNotExist()
    {
        $event = new Event();
        $this->assertNull($event->state);
    }

    public function testDelegateSetAttribute()
    {
        $event = Event::first();
        $event->state = 'MEXICO';
        $this->assertEquals('MEXICO', $event->venue->state);
    }

    public function testDelegateGetterGh98()
    {
        Venue::$use_custom_get_state_getter = true;

        $event = Event::first();
        $this->assertEquals('ny', $event->venue->state);
        $this->assertEquals('ny', $event->state);

        Venue::$use_custom_get_state_getter = false;
    }

    public function testDelegateSetterGh98()
    {
        Venue::$use_custom_set_state_setter = true;

        $event = Event::first();
        $event->state = 'MEXICO';
        $this->assertEquals('MEXICO#', $event->venue->state);

        Venue::$use_custom_set_state_setter = false;
    }

    public function testTableNameWithUnderscores()
    {
        $this->assertNotNull(AwesomePerson::first());
    }

    public function testModelShouldDefaultAsNewRecord()
    {
        $author = new Author();
        $this->assertTrue($author->isNewRecord());
    }

    public function testSetter()
    {
        $author = new Author();
        $author->password = 'plaintext';
        $this->assertEquals(md5('plaintext'), $author->encrypted_password);
    }

    public function testSetterWithSameNameAsAnAttribute()
    {
        $author = new Author();
        $author->name = 'bob';
        $this->assertEquals('BOB', $author->name);
    }

    public function testGetter()
    {
        $book = Book::first();
        $this->assertEquals(\strtoupper($book->name), $book->upper_name);
    }

    public function testGetterWithSameNameAsAnAttribute()
    {
        Book::$use_custom_get_name_getter = true;
        $book = new Book;
        $book->name = 'bob';
        $this->assertEquals('BOB', $book->name);
        Book::$use_custom_get_name_getter = false;
    }

    public function testSettingInvalidDateShouldSetDateToNull()
    {
        $author = new Author();
        $author->created_at = 'CURRENT_TIMESTAMP';
        $this->assertNull($author->created_at);
    }

    public function testTableName()
    {
        $this->assertEquals('authors', Author::tableName());
    }

    /**
     * @expectedException Activerecord\ActiverecordException
     */
    public function testUndefinedInstanceMethod()
    {
        Author::first()->find_by_name('sdf');
    }

    public function testClearCacheForSpecificClass()
    {
        $book_table1 = Table::load('Book');
        $book_table2 = Table::load('Book');
        Table::clear_cache('Book');
        $book_table3 = Table::load('Book');

        $this->assertTrue($book_table1 === $book_table2);
        $this->assertTrue($book_table1 !== $book_table3);
    }

    public function testFlagDirty()
    {
        $author = new Author();
        $author->flagDirty('some_date');
        $this->assertHasKeys('some_date', $author->dirtyAttributes());
        $this->assertTrue($author->attributeIsDirty('some_date'));
        $author->save();
        $this->assertFalse($author->attributeIsDirty('some_date'));
    }

    public function testFlagDirtyAttributeWhichDoesNotExit()
    {
        $author = new Author();
        $author->flagDirty('some_inexistant_property');
        $this->assertNull($author->dirtyAttributes());
        $this->assertFalse($author->attributeIsDirty('some_inexistant_property'));
    }

    public function testGh245DirtyAttributeShouldNotRaisePhpNoticeIfNotDirty()
    {
        $event = new Event(array(
            'title' => "Fun"));
        $this->assertFalse($event->attributeIsDirty('description'));
        $this->assertTrue($event->attributeIsDirty('title'));
    }

    public function testAssigningPhpDatetimeGetsConvertedToDateClassWithDefaults()
    {
        $author = new Author();
        $author->created_at = $now = new \DateTime();
        $this->assertIsA("Activerecord\\DateTime", $author->created_at);
        $this->assertDatetimeEquals($now, $author->created_at);
    }

    public function testAssigningPhpDatetimeGetsConvertedToDateClassWithCustomDateClass()
    {
        Config::instance()->setDateClass('\\DateTime'); // use PHP built-in DateTime
        $author = new Author();
        $author->created_at = $now = new \DateTime();
        $this->assertIsA("DateTime", $author->created_at);
        $this->assertDatetimeEquals($now, $author->created_at);
    }

    public function testAssigningFromMassAssignmentPhpDatetimeGetsConvertedToArDatetime()
    {
        $author = new Author(array(
            'created_at' => new \DateTime()));
        $this->assertIsA("Activerecord\\DateTime", $author->created_at);
    }

    public function testGetRealAttributeName()
    {
        $venue = new Venue();
        $this->assertEquals('name', $venue->getRealAttributeName('name'));
        $this->assertEquals('name', $venue->getRealAttributeName('marquee'));
        $this->assertEquals(null, $venue->getRealAttributeName('invalid_field'));
    }

    public function testIdSetterWorksWithTableWithoutPkNamedAttribute()
    {
        $author = new Author(['id' => 123]);
        $this->assertEquals(123, $author->author_id);
    }

    public function testQuery()
    {
        $row = Author::query('SELECT COUNT(*) AS n FROM authors', null)->fetch();
        $this->assertTrue($row['n'] > 1);

        $row = Author::query('SELECT COUNT(*) AS n FROM authors WHERE name=?',
                        ['Tito'])->fetch();
        $this->assertEquals(['n' => 1], $row);
    }

}