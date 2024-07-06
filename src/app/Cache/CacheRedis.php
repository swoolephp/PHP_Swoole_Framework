<?php

namespace App\Cache;

class CacheRedis implements Cache
{
    protected $pool;

    public function __construct($config)
    {
        $this->pool = new CachePool($config);
    }

    public function get($key)
    {
        return $this->pool->get($key);
    }

    public function set($key, $value, $timeout = 300)
    {
        return $this->pool->set($key, $value, $timeout);
    }

    public function del($key)
    {
        return $this->pool->del($key);
    }

    public function exists($key)
    {
        return $this->pool->exists($key);
    }

    public function close()
    {
        $this->pool->close();
    }
}
