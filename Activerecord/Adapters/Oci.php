<?php

namespace Activerecord\Adapters;

use Activerecord\Column;
use Activerecord\Connection;
use Activerecord\Exceptions\ExceptionDatabase;
use Activerecord\Inflector;

/**
 * Adapter for OCI (not completed yet).
 *
 * @package Activerecord
 */
class Oci
        extends Connection
{

    static $quote_character = '';
    static $default_port = 1521;
    public $dsn_params;

    protected function __construct($info)
    {
        try
        {
            $this->dsn_params = isset($info->charset) ? ";charset=$info->charset"
                        : "";
            $this->connection = new \PDO("oci:dbname=//$info->host/$info->db$this->dsn_params",
                    $info->user, $info->pass, static::$pdo_options);
        }
        catch (\PDOException $e)
        {
            throw new ExceptionDatabase($e);
        }
    }

    public function supportsSequences()
    {
        return true;
    }

    public function getNextSequenceValue($sequence_name)
    {
        return $this->queryAndFetchOne('SELECT '.$this->nextSequenceValue($sequence_name).' FROM dual');
    }

    public function nextSequenceValue($sequence_name)
    {
        return "$sequence_name.nextval";
    }

    public function dateToString($datetime)
    {
        return $datetime->format('d-M-Y');
    }

    public function datetimeToString($datetime)
    {
        return $datetime->format('d-M-Y h:i:s A');
    }

    // $string = DD-MON-YYYY HH12:MI:SS(\.[0-9]+) AM
    public function stringToDatetime($string)
    {
        return parent::stringToDatetime(\str_replace('.000000', '', $string));
    }

    public function limit($sql, $offset, $limit)
    {
        $offset = \intval($offset);
        $stop = $offset + \intval($limit);
        return
                "SELECT * FROM (SELECT a.*, rownum ar_rnum__ FROM ($sql) a ".
                "WHERE rownum <= $stop) WHERE ar_rnum__ > $offset";
    }

    public function queryColumnInfo($table)
    {
        $sql = "SELECT c.column_name, c.data_type, c.data_length, c.data_scale, c.data_default, c.nullable, ".
                "(SELECT a.constraint_type ".
                "FROM all_constraints a, all_cons_columns b ".
                "WHERE a.constraint_type='P' ".
                "AND a.constraint_name=b.constraint_name ".
                "AND a.table_name = t.table_name AND b.column_name=c.column_name) AS pk ".
                "FROM user_tables t ".
                "INNER JOIN user_tab_columns c on(t.table_name=c.table_name) ".
                "WHERE t.table_name=?";

        $values = [\strtoupper($table)];
        return $this->query($sql, $values);
    }

    public function queryForTables()
    {
        return $this->query("SELECT table_name FROM user_tables");
    }

    public function createColumn(&$column)
    {
        $column['column_name'] = \strtolower($column['column_name']);
        $column['data_type'] = \strtolower(\preg_replace('/\(.*?\)/', '',
                        $column['data_type']));

        if ($column['data_default'] !== null)
        {
            $column['data_default'] = \trim($column['data_default'], "' ");
        }

        if ($column['data_type'] == 'number')
        {
            if ($column['data_scale'] > 0)
            {
                $column['data_type'] = 'decimal';
            }
            elseif ($column['data_scale'] == 0)
            {
                $column['data_type'] = 'int';
            }
        }

        $c = new Column();
        $c->inflected_name = Inflector::instance()->variablize($column['column_name']);
        $c->name = $column['column_name'];
        $c->nullable = $column['nullable'] == 'Y' ? true : false;
        $c->pk = $column['pk'] == 'P' ? true : false;
        $c->length = $column['data_length'];

        if ($column['data_type'] == 'timestamp')
        {
            $c->raw_type = 'datetime';
        }
        else
        {
            $c->raw_type = $column['data_type'];
        }

        $c->mapRawType();
        $c->default = $c->cast($column['data_default'], $this);

        return $c;
    }

    public function setEncoding($charset)
    {
        // is handled in the constructor
    }

    public function nativeDatabaseTypes()
    {
        return [
            'primary_key' => "NUMBER(38) NOT NULL PRIMARY KEY",
            'string' => ['name' => 'VARCHAR2',
                'length' => 255],
            'text' => ['name' => 'CLOB'],
            'integer' => ['name' => 'NUMBER',
                'length' => 38],
            'float' => ['name' => 'NUMBER'],
            'datetime' => ['name' => 'DATE'],
            'timestamp' => ['name' => 'DATE'],
            'time' => ['name' => 'DATE'],
            'date' => ['name' => 'DATE'],
            'binary' => ['name' => 'BLOB'],
            'boolean' => ['name' => 'NUMBER',
                'length' => 1]
        ];
    }

}