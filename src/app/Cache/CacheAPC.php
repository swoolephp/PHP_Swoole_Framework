<?php

namespace App\Cache;

class CacheAPC implements Cache
{
    public function __construct($config)
    {
        // No configuration needed for APC
    }

    public function get($key)
    {
        return apcu_fetch($key);
    }

    public function set($key, $value, $timeout = 300)
    {
        return apcu_store($key, $value, $timeout);
    }

    public function del($key)
    {
        return apcu_delete($key);
    }

    public function exists($key)
    {
        return apcu_exists($key);
    }

    public function close()
    {
        // No close operation needed for APC-based cache
    }
}
