<?php
/**
 * @package ActiveRecord
 */

namespace Activerecord;

use Activerecord\Cache;
use Activerecord\Exceptions\ExceptionConfig;
use Activerecord\Reflections;
use Activerecord\Serializers\AbstractSerialize;
use Activerecord\Singleton;

/**
 * Manages configuration options for ActiveRecord.
 *
 * <code>
 * ActiveRecord::initialize(function($cfg) {
 *   $cfg->set_model_home('models');
 *   $cfg->setConnections(array(
 *     'development' => 'mysql://user:pass@development.com/awesome_development',
 *     'production' => 'mysql://user:pass@production.com/awesome_production'));
 * });
 * </code>
 *
 * @package ActiveRecord
 */
class Config
        extends Singleton
{

    /**
     * Name of the connection to use by default.
     *
     * <code>
     * ActiveRecord\Config::initialize(function($cfg) {
     *   $cfg->setModelDirectory('/your/app/models');
     *   $cfg->setConnections(array(
     *     'development' => 'mysql://user:pass@development.com/awesome_development',
     *     'production' => 'mysql://user:pass@production.com/awesome_production'));
     * });
     * </code>
     *
     * This is a singleton class so you can retrieve the {@link Singleton} instance by doing:
     *
     * <code>
     * $config = ActiveRecord\Config::instance();
     * </code>
     *
     * @var string
     */
    private $default_connection = 'development';

    /**
     * Contains the list of database connection strings.
     *
     * @var array
     */
    private $connections = [];

    /**
     * Directory for the auto_loading of model classes.
     *
     * @see \activerecord_autoload
     * @var string
     */
    private $model_directory;

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
     * The format to serialize DateTime values into.
     *
     * @var string
     */
    // original
    //private $date_format = \DateTime::ISO8601;

    /**
     * Allows config initialization using a closure.
     *
     * This method is just syntatic sugar.
     *
     * <code>
     * ActiveRecord\Config::initialize(function($cfg) {
     *   $cfg->setModelDirectory('/path/to/your/model_directory');
     *   $cfg->setConnections(array(
     *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
     * });
     * </code>
     *
     * You can also initialize by grabbing the singleton object:
     *
     * <code>
     * $cfg = ActiveRecord\Config::instance();
     * $cfg->setModelDirectory('/path/to/your/model_directory');
     * $cfg->setConnections(array('development' =>
     *   'mysql://username:password@localhost/database_name'));
     * </code>
     *
     * @param \Closure $initializer A closure
     * @return void
     */
    public static function initialize(\Closure $initializer)
    {
        $initializer(parent::instance());
    }

    /**
     * Sets the list of database connection strings.
     *
     * <code>
     * $config->setConnections(array(
     *     'development' => 'mysql://username:password@127.0.0.1/database_name'));
     * </code>
     *
     * @param array $connections Array of connections
     * @param string $default_connection Optionally specify the default_connection
     * @return void
     * @throws Config
     */
    public function setConnections($connections, $default_connection = null)
    {
        if (!\is_array($connections))
        {
            throw new ExceptionConfig("Connections must be an array");
        }

        if ($default_connection)
        {
            $this->setDefaultConnection($default_connection);
        }

        $this->connections = $connections;
    }

    /**
     * Returns the connection strings array.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Returns a connection string if found otherwise null.
     *
     * @param string $name Name of connection to retrieve
     * @return string connection info for specified connection name
     */
    public function getConnection($name)
    {
        if (\array_key_exists($name, $this->connections))
        {
            return $this->connections[$name];
        }

        return null;
    }

    /**
     * Returns the default connection string or null if there is none.
     *
     * @return string
     */
    public function getDefaultConnectionString()
    {
        return \array_key_exists($this->default_connection, $this->connections) ?
                $this->connections[$this->default_connection] : null;
    }

    /**
     * Returns the name of the default connection.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return $this->default_connection;
    }

    /**
     * Set the name of the default connection.
     *
     * @param string $name Name of a connection in the connections array
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->default_connection = $name;
    }

    /**
     * Sets the directory where models are located.
     *
     * @param string $dir Directory path containing your models
     * @return void
     */
    public function setModelDirectory($dir)
    {
        $this->model_directory = $dir;
    }

    /**
     * Returns the model directory.
     *
     * @return string
     * @throws Config if specified directory was not found
     */
    public function getModelDirectory()
    {

        foreach (\glob("$this->model_directory/*.php") as $filename)
        {
            require_once $filename;
        }
        if ($this->model_directory && !\file_exists($this->model_directory))
        {
            throw new ExceptionConfig('Invalid or non-existent directory: '.$this->model_directory);
        }

        return $this->model_directory;
    }

    /**
     * Turn on/off logging
     *
     * @param boolean $bool
     * @return void
     */
    public function setLogging($bool)
    {
        $this->logging = (bool) $bool;
    }

    /**
     * Sets the logger object for future SQL logging
     *
     * @param object $logger
     * @return void
     * @throws ConfigException if Logger objecct does not implement public log()
     */
    public function setLogger($logger)
    {
        $reflect = Reflections::instance()->add($logger)->get($logger);

        if (!$reflect->getMethod('log') || !$reflect->getMethod('log')->isPublic())
        {
            throw new ExceptionConfig("Logger object must implement a public log method");
        }

        $this->logger = $logger;
    }

    /**
     * Return whether or not logging is on
     *
     * @return boolean
     */
    public function getLogging()
    {
        return $this->logging;
    }

    /**
     * Returns the logger
     *
     * @return object
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @deprecated
     */
    public function getDateFormat()
    {
        trigger_error('Use Activerecord\Serializers\AbstractSerialize::$datetime_format. Config::getDateFormat() has been deprecated.',
                E_USER_DEPRECATED);
        return AbstractSerialize::$datetime_format;
    }

    /**
     * @deprecated
     */
    public function setDateFormat($format)
    {
        trigger_error('Use Activerecord\Serializers\AbstractSerialize::$datetime_format. Config::setDateFormat() has been deprecated.',
                E_USER_DEPRECATED);
        AbstractSerialize::$datetime_format = $format;
    }

    /**
     * Sets the url for the cache server to enable query caching.
     *
     * Only table schema queries are cached at the moment. A general query cache
     * will follow.
     *
     * Example:
     *
     * <code>
     * $config->setCache("memcached://localhost");
     * $config->setCache("memcached://localhost",array("expire" => 60));
     * </code>
     *
     * @param string $url Url to your cache server.
     * @param array $options Array of options
     */
    public function setCache($url, $options = [])
    {
        Cache::initialize($url, $options);
    }

}