<?php

namespace App\Pools;

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Redis;

class RedisPool {
    protected $pool;
    protected $config;
    protected $size;

    public function __construct($config, $size = 10) {
        $this->config = $config;
        $this->size = $size;
        $this->pool = new Channel($size);
    }

    public function get() {
        if ($this->pool->isEmpty()) {
            $redis = new Redis();
            $connected = $redis->connect($this->config['host'], $this->config['port']);
            if (!$connected) {
                throw new \Exception("Failed to connect to Redis at {$this->config['host']}:{$this->config['port']}");
            }
            if (!empty($this->config['password'])) {
                $redis->auth($this->config['password']);
            }
            return $redis;
        }
        return $this->pool->pop();
    }

    public function put($redis) {
        if ($this->pool->isFull()) {
            $redis->close();
        } else {
            $this->pool->push($redis);
        }
    }

    public function close() {
        while (!$this->pool->isEmpty()) {
            $redis = $this->pool->pop();
            $redis->close();
        }
    }
}
