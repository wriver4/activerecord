<?php
/**
 * @package ActiveRecord
 */

namespace Activerecord;

use Activerecord\DateTime;
use Activerecord\Exceptions\exDatabase;
use Activerecord\Config;

/**
 * The base class for database connection adapters.
 *
 * @package ActiveRecord
 */
abstract class Connection
{

    /**
     * The PDO connection object.
     * @var mixed
     */
    public $connection;

    /**
     * The last query run.
     * @var string
     */
    public $last_query;

    /**
     * Switch for logging.
     *
     * @var bool
     */
    private $logging = false;

    /**
     * Contains a Logger object that must impelement a log() method.
     *
     * @var object
     */
    private $logger;

    /**
     * The name of the protocol that is used.
     * @var string
     */
    public $protocol;

    /**
     * Database's date format
     * @var string
     */
    static $date_format = 'Y-m-d';

    /**
     * Database's datetime format
     * @var string
     */
    static $datetime_format = 'Y-m-d H:i:s T';

    /**
     * Default PDO options to set for each connection.
     * @var array
     */
    static $PDO_OPTIONS = [
        \PDO::ATTR_CASE => \PDO::CASE_LOWER,
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_ORACLE_NULLS => \PDO::NULL_NATURAL,
        \PDO::ATTR_STRINGIFY_FETCHES => false];

    /**
     * The quote character for stuff like column and field names.
     * @var string
     */
    static $QUOTE_CHARACTER = '`';

    /**
     * Default port.
     * @var int
     */
    static $DEFAULT_PORT = 0;

    /**
     * Retrieve a database connection.
     *
     * @param string $connection_string_or_connection_name A database connection string (ex. mysql://user:pass@host[:port]/dbname)
     *   Everything after the protocol:// part is specific to the connection adapter.
     *   OR
     *   A connection name that is set in ActiveRecord\Config
     *   If null it will use the default connection specified by ActiveRecord\Config->set_default_connection
     * @return Connection
     * @see parseConnectionUrl
     */
    public static function instance($connection_string_or_connection_name = null)
    {
        $config = Config::instance();

        if (strpos($connection_string_or_connection_name, '://') === false)
        {
            $connection_string = $connection_string_or_connection_name ?
                    $config->getConnection($connection_string_or_connection_name)
                        :
                    $config->getDefaultConnectionString();
        }
        else
        {
            $connection_string = $connection_string_or_connection_name;
        }

        if (!$connection_string)
        {
            throw new exDatabase("Empty connection string");
        }

        $info = static::parseConnectionUrl($connection_string);
        $fqclass = static::loadAdapter($info->protocol);

        try
        {
            $connection = new $fqclass($info);
            $connection->protocol = $info->protocol;
            $connection->logging = $config->getLogging();
            $connection->logger = $connection->logging ? $config->getLogger() : null;

            if (isset($info->charset))
            {
                $connection->setEncoding($info->charset);
            }
        }
        catch (\PDOException $e)
        {
            throw new exDatabase($e);
        }
        return $connection;
    }

    /**
     * Loads the specified class for an adapter.
     *
     * @param string $adapter Name of the adapter.
     * @return string The full name of the class including namespace.
     */
    private static function loadAdapter($adapter)
    {
        $class = \ucwords($adapter);
        $fqclass = 'ActiveRecord\\Adapters\\'.$class;
        $source = __DIR__."/Adapters/$class.php";

        if (!\file_exists($source))
        {
            throw new exDatabase("$fqclass not found!");
        }

        require_once($source);
        return $fqclass;
    }

    /**
     * Use this for any adapters that can take connection info in the form below
     * to set the adapters connection info.
     *
     * <code>
     * protocol://username:password@host[:port]/dbname
     * protocol://urlencoded%20username:urlencoded%20password@host[:port]/dbname?decode=true
     * protocol://username:password@unix(/some/file/path)/dbname
     * </code>
     *
     * Sqlite has a special syntax, as it does not need a database name or user authentication:
     *
     * <code>
     * sqlite://file.db
     * sqlite://../relative/path/to/file.db
     * sqlite://unix(/absolute/path/to/file.db)
     * sqlite://windows(c%2A/absolute/path/to/file.db)
     * </code>
     *
     * @param string $connection_url A connection URL
     * @return object the parsed URL as an object.
     */
    public static function parseConnectionUrl($connection_url)
    {
        $url = @\parse_url($connection_url);

        if (!isset($url['host']))
        {
            throw new exDatabase('Database host must be specified in the connection string. If you want to specify an absolute filename, use e.g. sqlite://unix(/path/to/file)');
        }

        $info = new \stdClass();
        $info->protocol = $url['scheme'];
        $info->host = $url['host'];
        $info->db = isset($url['path']) ? \substr($url['path'], 1) : null;
        $info->user = isset($url['user']) ? $url['user'] : null;
        $info->pass = isset($url['pass']) ? $url['pass'] : null;

        $allow_blank_db = ($info->protocol == 'sqlite');

        if ($info->host == 'unix(')
        {
            $socket_database = $info->host.'/'.$info->db;

            if ($allow_blank_db)
            {
                $unix_regex = '/^unix\((.+)\)\/?().*$/';
            }
            else
            {
                $unix_regex = '/^unix\((.+)\)\/(.+)$/';
            }

            if (\preg_match_all($unix_regex, $socket_database, $matches) > 0)
            {
                $info->host = $matches[1][0];
                $info->db = $matches[2][0];
            }
        }
        elseif (\substr($info->host, 0, 8) == 'windows(')
        {
            $info->host = \urldecode(\substr($info->host, 8).'/'.\substr($info->db,
                            0, -1));
            $info->db = null;
        }

        if ($allow_blank_db && $info->db)
        {
            $info->host .= '/'.$info->db;
        }

        if (isset($url['port']))
        {
            $info->port = $url['port'];
        }

        if (\strpos($connection_url, 'decode=true') !== false)
        {
            if ($info->user)
            {
                $info->user = \urldecode($info->user);
            }

            if ($info->pass)
            {
                $info->pass = \urldecode($info->pass);
            }
        }

        if (isset($url['query']))
        {
            foreach (\explode('/&/', $url['query']) as $pair)
            {
                list($name, $value) = \explode('=', $pair);

                if ($name == 'charset')
                {
                    $info->charset = $value;
                }
            }
        }

        return $info;
    }

    /**
     * Class Connection is a singleton. Access it via instance().
     *
     * @param array $info Array containing URL parts
     * @return Connection
     */
    protected function __construct($info)
    {
        try
        {
            // unix sockets start with a /
            if ($info->host[0] != '/')
            {
                $host = "host=$info->host";

                if (isset($info->port))
                {
                    $host .= ";port=$info->port";
                }
            }
            else
            {
                $host = "unix_socket=$info->host";
            }

            $this->connection = new \PDO("$info->protocol:$host;dbname=$info->db",
                    $info->user, $info->pass, static::$PDO_OPTIONS);
        }
        catch (\PDOException $e)
        {
            throw new exDatabase($e);
        }
    }

    /**
     * Retrieves column meta data for the specified table.
     *
     * @param string $table Name of a table
     * @return array An array of {@link Column} objects.
     */
    public function columns($table)
    {
        $columns = [];
        $sth = $this->queryColumnInfo($table);

        while (($row = $sth->fetch()))
        {
            $c = $this->create_column($row);
            $columns[$c->name] = $c;
        }
        return $columns;
    }

    /**
     * Escapes quotes in a string.
     *
     * @param string $string The string to be quoted.
     * @return string The string with any quotes in it properly escaped.
     */
    public function escape($string)
    {
        return $this->connection->quote($string);
    }

    /**
     * Retrieve the insert id of the last model saved.
     *
     * @param string $sequence Optional name of a sequence to use
     * @return int
     */
    public function insertId($sequence = null)
    {
        return $this->connection->lastInsertId($sequence);
    }

    /**
     * Execute a raw SQL query on the database.
     *
     * @param string $sql Raw SQL string to execute.
     * @param array &$values Optional array of bind values
     * @return mixed A result set object
     */
    public function query($sql, &$values = [])
    {
        if ($this->logging)
        {
            $this->logger->log($sql);
            if ($values)
            {
                $this->logger->log($values);
            }
        }

        $this->last_query = $sql;

        try
        {
            if (!($sth = $this->connection->prepare($sql)))
            {
                throw new exDatabase($this);
            }
        }
        catch (\PDOException $e)
        {
            throw new exDatabase($this);
        }

        $sth->setFetchMode(\PDO::FETCH_ASSOC);

        try
        {
            if (!$sth->execute($values))
            {
                throw new exDatabase($this);
            }
        }
        catch (\PDOException $e)
        {
            throw new exDatabase($e);
        }
        return $sth;
    }

    /**
     * Execute a query that returns maximum of one row with one field
     * and return it.
     *
     * @param string $sql Raw SQL string to execute.
     * @param array &$values Optional array of values to bind to the query.
     * @return string
     */
    public function queryAndFetchOne($sql, &$values = [])
    {
        $sth = $this->query($sql, $values);
        $row = $sth->fetch(\PDO::FETCH_NUM);
        return $row[0];
    }

    /**
     * Execute a raw SQL query and fetch the results.
     *
     * @param string $sql Raw SQL string to execute.
     * @param \Closure $handler Closure that will be passed the fetched results.
     */
    public function queryAndFetch($sql, \Closure $handler)
    {
        $sth = $this->query($sql);

        while (($row = $sth->fetch(\PDO::FETCH_ASSOC)))
        {
            $handler($row);
        }
    }

    /**
     * Returns all tables for the current database.
     * @param char $table an existing table name
     * @param char $db db name
     * @return array Array containing table names.
     * example $list = Activerecord\Connection::tables('permissions', 'site_rbac_com');
     */
    public function tables($table, $db)
    {
        $model = rtrim($table, "s");
        foreach ($model::findBySql('SHOW TABLES') as $value)
        {
            $tables[] = $value->{"tables_in_".$db};
        }
        return $tables;
    }

    /**
     * Starts a transaction.
     */
    public function transaction()
    {
        if (!$this->connection->beginTransaction())
        {
            throw new exDatabase($this);
        }
    }

    /**
     * Commits the current transaction.
     */
    public function commit()
    {
        if (!$this->connection->commit())
        {
            throw new exDatabase($this);
        }
    }

    /**
     * Rollback a transaction.
     */
    public function rollback()
    {
        if (!$this->connection->rollback())
        {
            throw new exDatabase($this);
        }
    }

    /**
     * Tells you if this adapter supports sequences or not.
     *
     * @return boolean
     */
    function supportsSequences()
    {
        return false;
    }

    /**
     * Return a default sequence name for the specified table.
     *
     * @param string $table Name of a table
     * @param string $column_name Name of column sequence is for
     * @return string sequence name or null if not supported.
     */
    public function getSequenceName($table, $column_name)
    {
        return "{$table}_seq";
    }

    /** todo fix this and model
     * Return SQL for getting the next value in a sequence.
     *
     * @param string $sequence_name Name of the sequence
     * @return string
     */
    public function nextSequenceValue($sequence_name)
    {
        return null;
    }

    /**
     * Quote a name like table names and field names.
     *
     * @param string $string String to quote.
     * @return string
     */
    public function quoteName($string)
    {
        return $string[0] === static::$QUOTE_CHARACTER || $string[\strlen($string)
                - 1] === static::$QUOTE_CHARACTER ?
                $string : static::$QUOTE_CHARACTER.$string.static::$QUOTE_CHARACTER;
    }

    /**
     * Return a date time formatted into the database's date format.
     *
     * @param DateTime $datetime The DateTime object
     * @return string
     */
    public function dateToString($datetime)
    {
        return $datetime->format(static::$date_format);
    }

    /**
     * Return a date time formatted into the database's datetime format.
     *
     * @param DateTime $datetime The DateTime object
     * @return string
     */
    public function datetimeToString($datetime)
    {
        return $datetime->format(static::$datetime_format);
    }

    /**
     * Converts a string representation of a datetime into a DateTime object.
     *
     * @param string $string A datetime in the form accepted by date_create()
     * @return DateTime
     */
    public function stringToDatetime($string)
    {
        $date = date_create($string);
        $errors = \DateTime::getLastErrors();

        if ($errors['warning_count'] > 0 || $errors['error_count'] > 0)
        {
            return null;
        }

        return new DateTime($date->format(static::$datetime_format));
    }

    /**
     * Adds a limit clause to the SQL query.
     *
     * @param string $sql The SQL statement.
     * @param int $offset Row offset to start at.
     * @param int $limit Maximum number of rows to return.
     * @return string The SQL query that will limit results to specified parameters
     */
    abstract function limit($sql, $offset, $limit);

    /**
     * Query for column meta info and return statement handle.
     *
     * @param string $table Name of a table
     * @return \PDOStatement
     */
    abstract public function queryColumnInfo($table);

    /**
     * Executes query to specify the character set for this connection.
     */
    abstract function setEncoding($charset);

    /*
     * Returns an array mapping of native database types
     */

    abstract public function nativeDatabaseTypes();

    /**
     * Specifies whether or not adapter can use LIMIT/ORDER clauses with
     * DELETE & UPDATE operations
     *
     * @internal
     * @returns boolean (FALSE by default)
     */
    public function acceptsLimitAndOrderForUpdateAndDelete()
    {
        return false;
    }

}
