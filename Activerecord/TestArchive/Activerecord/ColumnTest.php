<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord;

use Activerecord\Column;
use Activerecord\Config;
use Activerecord\ConnectionManager;
use Activerecord\DateTime;
use Activerecord\Exceptions\ExceptionDatabase;

/**
 * Description of ColumnTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class ColumnTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->column = new Column();
        try
        {
            $this->conn = ConnectionManager::getConnection(Config::instance()->getDefaultConnection());
        }
        catch (ExceptionDatabase $e)
        {
            $this->markTestSkipped('failed to connect using default connection. '.$e->getMessage());
        }
    }

    public function tearDown()
    {

    }

    public function assertMappedType($type, $raw_type)
    {
        $this->column->raw_type = $raw_type;
        $this->assertEquals($type, $this->column->mapRawType());
    }

    public function assertCast($type, $casted_value, $original_value)
    {
        $this->column->type = $type;
        $value = $this->column->cast($original_value, $this->conn);

        if ($original_value != null && ($type == Column::DATETIME || $type == Column::DATE))
        {
            $this->assertTrue($value instanceof DateTime);
        }
        else
        {
            $this->assertSame($casted_value, $value);
        }
    }

    public function testMapRawTypeDates()
    {
        $this->assertMappedType(Column::DATETIME, 'datetime');
        $this->assertMappedType(Column::DATE, 'date');
    }

    public function testMapRawTypeIntegers()
    {
        $this->assertMappedType(Column::INTEGER, 'integer');
        $this->assertMappedType(Column::INTEGER, 'int');
        $this->assertMappedType(Column::INTEGER, 'tinyint');
        $this->assertMappedType(Column::INTEGER, 'smallint');
        $this->assertMappedType(Column::INTEGER, 'mediumint');
        $this->assertMappedType(Column::INTEGER, 'bigint');
    }

    public function testMapRawTypeDecimals()
    {
        $this->assertMappedType(Column::DECIMAL, 'float');
        $this->assertMappedType(Column::DECIMAL, 'double');
        $this->assertMappedType(Column::DECIMAL, 'numeric');
        $this->assertMappedType(Column::DECIMAL, 'dec');
    }

    public function testMapRawTypeStrings()
    {
        $this->assertMappedType(Column::STRING, 'string');
        $this->assertMappedType(Column::STRING, 'varchar');
        $this->assertMappedType(Column::STRING, 'text');
    }

    public function testMapRawTypeDefaultToString()
    {
        $this->assertMappedType(Column::STRING, 'bajdslfjasklfjlksfd');
    }

    public function testMapRawTypeChangesIntegerToInt()
    {
        $this->column->raw_type = 'integer';
        $this->column->mapRawType();
        $this->assertEquals('int', $this->column->raw_type);
    }

    public function testCast()
    {
        $datetime = new DateTime('2001-01-01');
        $this->assertCast(Column::INTEGER, 1, '1');
        $this->assertCast(Column::INTEGER, 1, '1.5');
        $this->assertCast(Column::DECIMAL, 1.5, '1.5');
        $this->assertCast(Column::DATETIME, $datetime, '2001-01-01');
        $this->assertCast(Column::DATE, $datetime, '2001-01-01');
        $this->assertCast(Column::DATE, $datetime, $datetime);
        $this->assertCast(Column::STRING, 'bubble tea', 'bubble tea');
        $this->assertCast(Column::INTEGER, 4294967295, '4294967295');
        $this->assertCast(Column::INTEGER, '18446744073709551615',
                '18446744073709551615');

        // 32 bit
        if (PHP_INT_SIZE === 4)
        {
            $this->assertCast(Column::INTEGER, '2147483648',
                    (((float) PHP_INT_MAX) + 1));
        }
        // 64 bit
        elseif (PHP_INT_SIZE === 8)
        {
            $this->assertCast(Column::INTEGER, '9223372036854775808',
                    (((float) PHP_INT_MAX) + 1));
        }
    }

    public function testCastLeaveNullAlone()
    {
        $types = [Column::STRING,
            Column::INTEGER,
            Column::DECIMAL,
            Column::DATETIME,
            Column::DATE];

        foreach ($types as $type)
        {
            $this->assertCast($type, null, null);
        }
    }

    public function testEmptyAndNullDateStringsShouldReturnNull()
    {
        $column = new Column();
        $column->type = Column::DATE;
        $this->assertEquals(null, $column->cast(null, $this->conn));
        $this->assertEquals(null, $column->cast('', $this->conn));
    }

    public function testEmptyAndNullDatetimeStringsShouldReturnNull()
    {
        $column = new Column();
        $column->type = Column::DATETIME;
        $this->assertEquals(null, $column->cast(null, $this->conn));
        $this->assertEquals(null, $column->cast('', $this->conn));
    }

    public function testNativeDateTimeAttributeCopiesExactTz()
    {
        $dt = new \DateTime(null, new \DateTimeZone('America/New_York'));

        $column = new Column();
        $column->type = Column::DATETIME;

        $dt2 = $column->cast($dt, $this->conn);

        $this->assertEquals($dt->getTimestamp(), $dt2->getTimestamp());
        $this->assertEquals($dt->getTimeZone(), $dt2->getTimeZone());
        $this->assertEquals($dt->getTimeZone()->getName(),
                $dt2->getTimeZone()->getName());
    }

    public function testArDateTimeAttributeCopiesExactTz()
    {
        $dt = new DateTime(null, new \DateTimeZone('America/New_York'));

        $column = new Column();
        $column->type = Column::DATETIME;

        $dt2 = $column->cast($dt, $this->conn);

        $this->assertEquals($dt->getTimestamp(), $dt2->getTimestamp());
        $this->assertEquals($dt->getTimeZone(), $dt2->getTimeZone());
        $this->assertEquals($dt->getTimeZone()->getName(),
                $dt2->getTimeZone()->getName());
    }

}