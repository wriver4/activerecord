<?php

namespace Test;

use Activerecord\Exceptions\ExceptionDatabase;

class DatabaseLoader
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
            $this->dropTables();
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
            if ($this->db->protocol !== 'sqlite')
            {
                $this->db->query('TRUNCATE TABLE '.$this->quoteName($table));
            }
            else
            {
                $this->db->query('DELETE FROM '.$this->quoteName($table));
                $this->db->query('VACUUM');
            }


            $this->loadFixtureData($table);
        }

        $after_fixtures = $this->db->protocol.'-after-fixtures';
        try
        {
            $this->execSqlScript($after_fixtures);
        }
        catch (\Exception $e)
        {
            // pass
        }
    }

    public function dropTables()
    {
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

            if (\in_array($table, $tables) && $this->db->protocol !== 'sqlite')
            {
                $this->db->query('DROP TABLE '.$this->quoteName($table));
            }
            else
            {
                $this->db->query('DELETE FROM '.$this->quoteName($table));
                $this->db->query('VACUUM');
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
                //note to self might be a way to seperate unit from functional
                $this->db->query($sql);
            }
        }
    }

    public function getFixtureTables()
    {
        $tables = [];

        foreach (\glob(__dir__.'/Fixtures/Csv/*.csv') as $file)
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
        $fp = \fopen(__dir__."/Fixtures/Csv/$table.csv", 'r');
        $fields = \fgetcsv($fp);

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