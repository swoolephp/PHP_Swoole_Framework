<?php
namespace App\Libs;
use App\Core\Config;
use App\Libs\RedisPool;

class CacheRedis implements Cache
{
    protected $pool;

    public function __construct($poolSize = 10)
    {
        $config = Config::getInstance()->get('redis');
        $this->pool = RedisPool::getInstance($config, $poolSize);
    }

    public function get($key)
    {
        $redis = $this->pool->get();
        $result = $redis->get($key);
        $this->pool->put($redis);
        return $result;
    }

    public function set($key, $value, $timeout = 300)
    {
        $redis = $this->pool->get();
        $result = $redis->setex($key, $timeout, $value);
        $this->pool->put($redis);
        return $result;
    }

    public function del($key)
    {
        $redis = $this->pool->get();
        $result = $redis->del($key);
        $this->pool->put($redis);
        return $result;
    }

    public function exists($key)
    {
        $redis = $this->pool->get();
        $result = $redis->exists($key);
        $this->pool->put($redis);
        return $result;
    }

    public function close()
    {
        $this->pool->close();
    }
}
