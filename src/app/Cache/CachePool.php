<?php

namespace App\Cache;

use Swoole\Coroutine\Channel;
use Swoole\Timer;

class CachePool implements Cache
{
    protected $config;
    protected $pool;
    protected $connections = [];
    protected $idleTimeout;
    protected $cleanupInterval;

    public function __construct($config)
    {
        $this->config = $config;
        $poolSize = $config['pool_size'] ?? 10;
        $this->pool = new Channel($poolSize);
        $this->idleTimeout = $config['idle_timeout'] ?? 60;
        $this->cleanupInterval = ($config['cleanup_interval'] ?? 5000) * 1000;

        for ($i = 0; $i < $poolSize; $i++) {
            $this->pool->push($i);
            $this->connections[$i] = null;
        }

        Timer::tick($this->cleanupInterval, function() {
            $this->cleanup();
        });
    }

    protected function createConnection()
    {
        $driver = $this->config['driver'] ?? 'file';
        switch ($driver) {
            case 'redis':
                $redis = new \Swoole\Coroutine\Redis();
                $connected = $redis->connect($this->config['host'], $this->config['port']);
                if (!$connected) {
                    throw new \Exception("Failed to connect to Redis at {$this->config['host']}:{$this->config['port']}");
                }
                if (!empty($this->config['user']) && !empty($this->config['password'])) {
                    if (!$redis->auth([$this->config['user'], $this->config['password']])) {
                        throw new \Exception("Redis authentication failed with user and password");
                    }
                } elseif (!empty($this->config['password'])) {
                    if (!$redis->auth($this->config['password'])) {
                        throw new \Exception("Redis authentication failed with password");
                    }
                }
                return $redis;
            case 'file':
                return new CacheFile($this->config);
            case 'apc':
                return new CacheAPC($this->config);
            default:
                throw new \Exception("Unsupported cache driver: $driver");
        }
    }

    public function get($key)
    {
        $index = $this->pool->pop();
        if ($this->connections[$index] === null) {
            $this->connections[$index] = $this->createConnection();
        }
        $cache = $this->connections[$index];
        $result = $cache->get($key);
        $this->pool->push($index);
        return $result;
    }

    public function set($key, $value, $timeout = 300)
    {
        $index = $this->pool->pop();
        if ($this->connections[$index] === null) {
            $this->connections[$index] = $this->createConnection();
        }
        $cache = $this->connections[$index];
        $result = $cache->set($key, $value, $timeout);
        $this->pool->push($index);
        return $result;
    }

    public function del($key)
    {
        $index = $this->pool->pop();
        if ($this->connections[$index] === null) {
            $this->connections[$index] = $this->createConnection();
        }
        $cache = $this->connections[$index];
        $result = $cache->del($key);
        $this->pool->push($index);
        return $result;
    }

    public function exists($key)
    {
        $index = $this->pool->pop();
        if ($this->connections[$index] === null) {
            $this->connections[$index] = $this->createConnection();
        }
        $cache = $this->connections[$index];
        $result = $cache->exists($key);
        $this->pool->push($index);
        return $result;
    }

    protected function cleanup()
    {
        foreach ($this->connections as $index => $connection) {
            if ($connection !== null && (time() - $connection->lastUsed) > $this->idleTimeout) {
                if (method_exists($connection, 'close')) {
                    $connection->close();
                }
                $this->connections[$index] = null;
            }
        }
    }

    public function close()
    {
        foreach ($this->connections as $connection) {
            if ($connection !== null && method_exists($connection, 'close')) {
                $connection->close();
            }
        }
    }
}
