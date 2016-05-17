<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test\Activerecord;

use Activerecord\SQLBuilder;
use Activerecord\Table;

/**
 * Description of SQLBuilderTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class SQLBuilderTest
        extends \Test\Helpers\DatabaseTest
{

    protected $table_name = 'authors';
    protected $class_name = 'Author';
    protected $table;

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
        $this->sql = new SQLBuilder($this->conn, $this->table_name);
        $this->table = Table::load($this->class_name);
    }

    public function tearDown()
    {

    }

    protected function conditionFromUnderscoreString($name, $values = null,
            $map = null)
    {
        return SQLBuilder::createConditionsFromUnderscoredString($this->table->conn,
                        $name, $values, $map);
    }

    public function assertConditions($expected_sql, $values,
            $underscored_string, $map = null)
    {
        $cond = SQLBuilder::createConditionsFromUnderscoredString($this->table->conn,
                        $underscored_string, $values, $map);
        $this->assertSqlHas($expected_sql, \array_shift($cond));

        if ($values)
        {
            $this->assertEquals(\array_values(\array_filter($values,
                                    function($s)
                            {
                                return $s !== null;
                            })), \array_values($cond));
        }
        else
        {
            $this->assertEquals([], $cond);
        }
    }

    /**
     * @expectedException ActiveRecord\ActiveRecordException
     */
    public function testNoConnection()
    {
        new SQLBuilder(null, 'authors');
    }

    public function testNothing()
    {
        $this->assertEquals('SELECT * FROM authors', (string) $this->sql);
    }

    public function testWhereWithArray()
    {
        $this->sql->where("id=? AND name IN(?)", 1,
                ['Tito',
            'Mexican']);
        $this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name IN(?,?)",
                (string) $this->sql);
        $this->assertEquals([1,
            'Tito',
            'Mexican'], $this->sql->getWhereValues());
    }

    public function testWhereWithHash()
    {
        $this->sql->where(['id' => 1,
            'name' => 'Tito']);
        $this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name=?",
                (string) $this->sql);
        $this->assertEquals([1,
            'Tito'], $this->sql->getWhereValues());
    }

    public function testWhereWithHashAndArray()
    {
        $this->sql->where(['id' => 1,
            'name' => ['Tito',
                'Mexican']]);
        $this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name IN(?,?)",
                (string) $this->sql);
        $this->assertEquals([1,
            'Tito',
            'Mexican'], $this->sql->getWhereValues());
    }

    public function testGh134WhereWithHashAndNull()
    {
        $this->sql->where(['id' => 1,
            'name' => null]);
        $this->assertSqlHas("SELECT * FROM authors WHERE id=? AND name IS ?",
                (string) $this->sql);
        $this->assertEquals([1,
            null], $this->sql->getWhereValues());
    }

    public function testWhereWithNull()
    {
        $this->sql->where(null);
        $this->assertEquals('SELECT * FROM authors', (string) $this->sql);
    }

    public function testWhereWithNoArgs()
    {
        $this->sql->where();
        $this->assertEquals('SELECT * FROM authors', (string) $this->sql);
    }

    public function testOrder()
    {
        $this->sql->order('name');
        $this->assertEquals('SELECT * FROM authors ORDER BY name',
                (string) $this->sql);
    }

    public function testLimit()
    {
        $this->sql->limit(10)->offset(1);
        $this->assertEquals($this->conn->limit('SELECT * FROM authors', 1, 10),
                (string) $this->sql);
    }

    public function testSelect()
    {
        $this->sql->select('id,name');
        $this->assertEquals('SELECT id,name FROM authors', (string) $this->sql);
    }

    public function testJoins()
    {
        $join = 'inner join books on(authors.id=books.author_id)';
        $this->sql->joins($join);
        $this->assertEquals("SELECT * FROM authors $join", (string) $this->sql);
    }

    public function testGroup()
    {
        $this->sql->group('name');
        $this->assertEquals('SELECT * FROM authors GROUP BY name',
                (string) $this->sql);
    }

    public function testHaving()
    {
        $this->sql->having("created_at > '2009-01-01'");
        $this->assertEquals("SELECT * FROM authors HAVING created_at > '2009-01-01'",
                (string) $this->sql);
    }

    public function testAllClausesAfteWhereShouldBeCorrectlyOrdered()
    {
        $this->sql->limit(10)->offset(1);
        $this->sql->having("created_at > '2009-01-01'");
        $this->sql->order('name');
        $this->sql->group('name');
        $this->sql->where(['id' => 1]);
        $this->assertSqlHas($this->conn->limit("SELECT * FROM authors WHERE id=? GROUP BY name HAVING created_at > '2009-01-01' ORDER BY name",
                        1, 10), (string) $this->sql);
    }

    /**
     * @expectedException ActiveRecord\ActiveRecordException
     */
    public function testInsertRequiresHash()
    {
        $this->sql->insert([1]);
    }

    public function testInsert()
    {
        $this->sql->insert(['id' => 1,
            'name' => 'Tito']);
        $this->assertSqlHas("INSERT INTO authors(id,name) VALUES(?,?)",
                (string) $this->sql);
    }

    public function testInsertWithNull()
    {
        $this->sql->insert(['id' => 1,
            'name' => null]);
        $this->assertSqlHas("INSERT INTO authors(id,name) VALUES(?,?)",
                $this->sql->toString());
    }

    public function testUpdateWithHash()
    {
        $this->sql->update(['id' => 1,
            'name' => 'Tito'])->where('id=1 AND name IN(?)',
                ['Tito',
            'Mexican']);
        $this->assertSqlHas("UPDATE authors SET id=?, name=? WHERE id=1 AND name IN(?,?)",
                (string) $this->sql);
        $this->assertEquals([1,
            'Tito',
            'Tito',
            'Mexican'], $this->sql->bind_values());
    }

    public function testUpdateWithLimitAndOrder()
    {
        if (!$this->conn->acceptsLimitAndOrderForUpdateAndDelete()) $this->markTestSkipped('Only MySQL & Sqlite accept limit/order with UPDATE operation');

        $this->sql->update(['id' => 1])->order('name asc')->limit(1);
        $this->assertSqlHas("UPDATE authors SET id=? ORDER BY name asc LIMIT 1",
                $this->sql->toString());
    }

    public function testUpdateWithString()
    {
        $this->sql->update("name='Bob'");
        $this->assertSqlHas("UPDATE authors SET name='Bob'",
                $this->sql->toString());
    }

    public function testUpdateWithNull()
    {
        $this->sql->update(['id' => 1,
            'name' => null])->where('id=1');
        $this->assertSqlHas("UPDATE authors SET id=?, name=? WHERE id=1",
                $this->sql->toString());
    }

    public function testDelete()
    {
        $this->sql->delete();
        $this->assertEquals('DELETE FROM authors', $this->sql->toString());
    }

    public function testDeleteWithWhere()
    {
        $this->sql->delete('id=? or name in(?)', 1,
                ['Tito',
            'Mexican']);
        $this->assertEquals('DELETE FROM authors WHERE id=? or name in(?,?)',
                $this->sql->toString());
        $this->assertEquals([1,
            'Tito',
            'Mexican'], $this->sql->bind_values());
    }

    public function testDeleteWithHash()
    {
        $this->sql->delete(['id' => 1,
            'name' => ['Tito',
                'Mexican']]);
        $this->assertSqlHas("DELETE FROM authors WHERE id=? AND name IN(?,?)",
                $this->sql->toString());
        $this->assertEquals([1,
            'Tito',
            'Mexican'], $this->sql->getWhereValues());
    }

    public function testDeleteWithLimitAndOrder()
    {
        if (!$this->conn->acceptsLimitAndOrderForUpdateAndDelete())
        {
            $this->markTestSkipped('Only MySQL & Sqlite accept limit/order with DELETE operation');
        }

        $this->sql->delete(['id' => 1])->order('name asc')->limit(1);
        $this->assertSqlHas("DELETE FROM authors WHERE id=? ORDER BY name asc LIMIT 1",
                $this->sql->toString());
    }

    public function testReverseOrder()
    {
        $this->assertEquals('id ASC, name DESC',
                SQLBuilder::reverse_order('id DESC, name ASC'));
        $this->assertEquals('id ASC, name DESC , zzz ASC',
                SQLBuilder::reverse_order('id DESC, name ASC , zzz DESC'));
        $this->assertEquals('id DESC, name DESC',
                SQLBuilder::reverse_order('id, name'));
        $this->assertEquals('id DESC', SQLBuilder::reverse_order('id'));
        $this->assertEquals('', SQLBuilder::reverse_order(''));
        $this->assertEquals(' ', SQLBuilder::reverse_order(' '));
        $this->assertEquals(null, SQLBuilder::reverse_order(null));
    }

    public function testCreateConditionsFromUnderscoredString()
    {
        $this->assertConditions('id=? AND name=? OR z=?',
                [1,
            'Tito',
            'X'], 'id_and_name_or_z');
        $this->assertConditions('id=?', [1], 'id');
        $this->assertConditions('id IN(?)', [[
        1,
        2]], 'id');
    }

    public function testCreateConditionsFromUnderscoredStringWithNulls()
    {
        $this->assertConditions('id=? AND name IS NULL', [1,
            null], 'id_and_name');
    }

    public function testCreateConditionsFromUnderscoredStringWithMissingArgs()
    {
        $this->assertConditions('id=? AND name IS NULL OR z IS NULL',
                [1,
            null], 'id_and_name_or_z');
        $this->assertConditions('id IS NULL', null, 'id');
    }

    public function testCreateConditionsFromUnderscoredStringWithBlank()
    {
        $this->assertConditions('id=? AND name IS NULL OR z=?',
                [1,
            null,
            ''], 'id_and_name_or_z');
    }

    public function testCreateConditionsFromUnderscoredStringInvalid()
    {
        $this->assertEquals(null, $this->conditionFromUnderscoreString(''));
        $this->assertEquals(null, $this->conditionFromUnderscoreString(null));
    }

    public function testCreateConditionsFromUnderscoredStringWithMappedColumns()
    {
        $this->assertConditions('id=? AND name=?', [1,
            'Tito'], 'id_and_my_name', ['my_name' => 'name']);
    }

    public function testCreateHashFromUnderscoredString()
    {
        $values = [1,
            'Tito'];
        $hash = SQLBuilder::createHashFromUnderscoredString('id_and_my_name',
                        $values);
        $this->assertEquals(['id' => 1,
            'my_name' => 'Tito'], $hash);
    }

    public function testCreateHashFromUnderscoredStringWithMappedColumns()
    {
        $values = [1,
            'Tito'];
        $map = ['my_name' => 'name'];
        $hash = SQLBuilder::createHashFromUnderscoredString('id_and_my_name',
                        $values, $map);
        $this->assertEquals(['id' => 1,
            'name' => 'Tito'], $hash);
    }

    public function testWhereWithJoinsPrependsTableNameToFields()
    {
        $joins = 'INNER JOIN books ON (books.id = authors.id)';
        // joins needs to be called prior to where
        $this->sql->joins($joins);
        $this->sql->where(['id' => 1,
            'name' => 'Tito']);

        $this->assertSqlHas("SELECT * FROM authors $joins WHERE authors.id=? AND authors.name=?",
                (string) $this->sql);
    }

}