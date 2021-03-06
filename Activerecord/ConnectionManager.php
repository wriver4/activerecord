<?php
/**
 * @package Activerecord
 */

namespace Activerecord;

use Activerecord\Config;
use Activerecord\Connection;
use Activerecord\Singleton;

/**
 * Singleton to manage any and all database connections.
 *
 * @package Activerecord
 */
class ConnectionManager
        extends Singleton
{

    /**
     * Array of {@link Connection} objects.
     * @var array
     */
    static private $connections = [];

    /**
     * If $name is null then the default connection will be returned.
     *
     * @see Config
     * @param string $name Optional name of a connection
     * @return Connection
     */
    public static function getConnection($name = null)
    {
        $config = Config::instance();
        $name = $name ? $name : $config->getDefaultConnection();

        if (!isset(self::$connections[$name]) || !self::$connections[$name]->connection)
        {
            self::$connections[$name] = Connection::instance($config->getConnection($name));
        }

        return self::$connections[$name];
    }

    /**
     * Drops the connection from the connection manager. Does not actually close it since there
     * is no close method in PDO.
     *
     * @param string $name Name of the connection to forget about
     */
    public static function dropConnection($name = null)
    {
        $config = Config::instance();
        $name = $name ? $name : $config->getDefaultConnection();
        if (isset(self::$connections[$name]))
        {
            unset(self::$connections[$name]);
        }
    }

}
