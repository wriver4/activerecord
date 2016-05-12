<?php
/**
 * @package Activerecord
 */

namespace Activerecord\Adapters;

use Activerecord\Connection;
use Activerecord\Column;
use Activerecord\Inflector;

/**
 * Adapter for MySQL.
 * comment
 * @package Activerecord
 */
class Mysql
        extends Connection
{

    static $DEFAULT_PORT = 3306;

    public function limit($sql, $offset, $limit)
    {
        $offset = is_null($offset) ? '' : intval($offset).',';
        $limit = intval($limit);
        return "$sql LIMIT {$offset}$limit";
    }

    public function queryColumnInfo($table)
    {
        return $this->query("SHOW COLUMNS FROM $table");
    }

    public function queryForTables()
    {
        return $this->query('SHOW TABLES');
    }

    public function createColumn(&$column)
    {
        $c = new Column();
        $c->inflected_name = Inflector::instance()->variablize($column['field']);
        $c->name = $column['field'];
        $c->nullable = ($column['null'] === 'YES' ? true : false);
        $c->pk = ($column['key'] === 'PRI' ? true : false);
        $c->auto_increment = ($column['extra'] === 'auto_increment' ? true : false);

        if ($column['type'] == 'timestamp' || $column['type'] == 'datetime')
        {
            $c->raw_type = 'datetime';
            $c->length = 19;
        }
        elseif ($column['type'] == 'date')
        {
            $c->raw_type = 'date';
            $c->length = 10;
        }
        elseif ($column['type'] == 'time')
        {
            $c->raw_type = 'time';
            $c->length = 8;
        }
        else
        {
            \preg_match('/^([A-Za-z0-9_]+)(\(([0-9]+(,[0-9]+)?)\))?/',
                    $column['type'], $matches);

            $c->raw_type = (\count($matches) > 0 ? $matches[1] : $column['type']);

            if (\count($matches) >= 4) $c->length = \intval($matches[3]);
        }

        $c->mapRawType();
        $c->default = $c->cast($column['default'], $this);

        return $c;
    }

    public function setEncoding($charset)
    {
        $params = [$charset];
        $this->query('SET NAMES ?', $params);
    }

    public function acceptsLimitAndOrderForUpdateAndDelete()
    {
        return true;
    }

    public function nativeDatabaseTypes()
    {
        return [
            'primary_key' => 'int(11) UNSIGNED DEFAULT NULL auto_increment PRIMARY KEY',
            'string' => ['name' => 'varchar',
                'length' => 255],
            'text' => ['name' => 'text'],
            'integer' => ['name' => 'int',
                'length' => 11],
            'float' => ['name' => 'float'],
            'datetime' => ['name' => 'datetime'],
            'timestamp' => ['name' => 'datetime'],
            'time' => ['name' => 'time'],
            'date' => ['name' => 'date'],
            'binary' => ['name' => 'blob'],
            'boolean' => ['name' => 'tinyint',
                'length' => 1]
        ];
    }

}
