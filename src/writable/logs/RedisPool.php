<?php

namespace App\Libs;

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Redis;

class RedisPool
{
    protected static $instance;
    protected $pool;
    protected $size;
    protected $config;

    private function __construct($config, $size = 10)
    {
        $this->config = $config;
        $this->size = $size;

        $this->pool = new Channel($size);
        for ($i = 0; $i < $size; $i++) {
            $redis = $this->createConnection();
            $this->pool->push($redis);
        }
    }

    public static function getInstance($config, $size = 10)
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $size);
        }
        return self::$instance;
    }

    protected function createConnection()
    {
        try {
            $redis = @new Redis();
            $connected = $redis->connect($this->config['host'], $this->config['port']);
            if (!$connected) {
                throw new \Exception("Failed to connect to Redis at {$this->config['host']}:{$this->config['port']}");
            }
            if ($this->config['user'] && $this->config['password']) {
                if (!$redis->auth([$this->config['user'], $this->config['password']])) {
                    throw new \Exception("Redis authentication failed with user and password");
                }
            } elseif ($this->config['password']) {
                if (!$redis->auth($this->config['password'])) {
                    throw new \Exception("Redis authentication failed with password");
                }
            }
            return $redis;
        } catch (\Exception $e) {
            error_log("Redis connection error: " . $e->getMessage());
            return null;
        }
    }

    public function get()
    {
        return $this->pool->pop();
    }

    public function put($redis)
    {
        $this->pool->push($redis);
    }

    public function close()
    {
        while (!$this->pool->isEmpty()) {
            $redis = $this->pool->pop();
            $redis->close();
        }
    }
}
