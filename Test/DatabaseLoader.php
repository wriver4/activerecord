<?php

namespace Test;

use Activerecord\Exceptions\ExceptionDatabase;

class DatabaseLoader
// extends \PHPUnit_Framework_TestCase
{

    private $db;
    static $instances = [];

    public function __construct($db)
    {
        $this->db = $db;

        if (!isset(static::$instances[$db->protocol]))
        {
            static::$instances[$db->protocol] = 0;
        }

        if (static::$instances[$db->protocol] ++ == 0)
        {
            // drop and re-create the tables one time only
            // $this->db->query('DELETE FROM '.$this->quoteName($table));
            $this->dropTables();
            var_dump($this);
            $this->execSqlScript($db->protocol);
        }
    }

    public function resetTableData()
    {
        foreach ($this->getFixtureTables() as $table)
        {
            if ($this->db->protocol === 'oci' && $table === 'rm-bldg')
            {
                continue;
            }

            $this->db->query('DELETE FROM '.$this->quoteName($table));
            // $this->dropTables();
            $this->loadFixtureData($table);
        }

        //  $after_fixtures = $this->db->protocol.'-after-fixtures';
        // try
        //  {
        //      $this->execSqlScript($after_fixtures);
        //  }
        //  catch (\Exception $e)
        //  {
        //      // pass
        //  }
    }

    public function dropTables()
    {
        //$tables = $this->db->tables();
        $tables = $this->db->tables();
        foreach ($this->getFixtureTables() as $table)
        {
            if ($this->db->protocol === 'oci')
            {
                $table = \strtoupper($table);

                if ($table === 'RM-BLDG')
                {
                    continue;
                }
            }
            // var_dump($tables);
            var_dump('drop tables');
            var_dump($table);
            if (\in_array($table, $tables))
            {
                //$this->db->query('DROP TABLE IF EXISTS '.$this->quoteName($table));
                $this->db->query('DROP TABLE '.$this->quoteName($table));
            }

            if ($this->db->protocol === 'oci')
            {
                try
                {
                    $this->db->query("DROP SEQUENCE {$table}_seq");
                }
                catch (ExceptionDatabase $e)
                {
                    // ignore
                }
            }
        }
    }

    public function execSqlScript($file)
    {
        foreach (\explode(';', $this->getSql($file)) as $sql)
        {
            if (\trim($sql) != '')
            {
                //var_dump($this);
                $this->db->query($sql);
            }
        }
    }

    public function getFixtureTables()
    {
        $tables = [];

        foreach (\glob('/Fixtures/Csv/*.csv') as $file)
        {
            $info = pathinfo($file);
            $tables[] = $info['filename'];
        }

        return $tables;
    }

    public function getSql($file)
    {
        $file = __DIR__."/Fixtures/Sql/$file.sql";

        if (!\file_exists($file))
        {
            throw new \Exception("File not found: $file");
        }

        return \file_get_contents($file);
    }

    public function loadFixtureData($table)
    {
        $fp = \fopen("../Test/Fixtures/$table.csv", 'r');
        $fields = \fgetcsv($fp);
        var_dump($fields);
        if (!empty($fields))
        {
            $markers = \join(',', \array_fill(0, \count($fields), '?'));
            $table = $this->quoteName($table);

            foreach ($fields as &$name)
            {
                $name = $this->quoteName(\trim($name));
            }

            $fields = \join(',', $fields);

            while (($values = \fgetcsv($fp)))
            {
                $this->db->query("INSERT INTO $table($fields) VALUES($markers)",
                        $values);
            }
        }
        \fclose($fp);
    }

    public function quoteName($name)
    {
        if ($this->db->protocol === 'oci')
        {
            $name = \strtoupper($name);
        }

        return $this->db->quoteName($name);
    }

}