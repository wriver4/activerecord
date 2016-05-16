<?php

namespace Activerecord;

use Activerecord\Exceptions\ExceptionCache;

class Memcache
{

    const default_port = 11211;

    private $memcache;

    /**
     * Creates a Memcache instance.
     *
     * Takes an $options array w/ the following parameters:
     *
     * <ul>
     * <li><b>host:</b> host for the memcache server </li>
     * <li><b>port:</b> port for the memcache server </li>
     * </ul>
     * @param array $options
     */
    public function __construct($options)
    {
        $this->memcache = new \Memcache();
        $options['port'] = isset($options['port']) ? $options['port'] : self::default_port;

        if (!$this->memcache->connect($options['host'], $options['port'])) throw new ExceptionCache("Could not connect to $options[host]:$options[port]");
    }

    public function flush()
    {
        $this->memcache->flush();
    }

    public function read($key)
    {
        return $this->memcache->get($key);
    }

    public function write($key, $value, $expire)
    {
        $this->memcache->set($key, $value, null, $expire);
    }

    public function delete($key)
    {
        $this->memcache->delete($key);
    }

}