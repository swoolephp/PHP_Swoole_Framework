<?php

namespace App\Database;

use Swoole\Coroutine\Channel;
use Swoole\Timer;

abstract class DatabasePool {
    protected $config;
    protected $size;
    protected $pool;
    protected $connections;
    protected $lastUsed;
    protected $maxIdleTime;
    protected $cleanupInterval;

    public function __construct($config, $size = 5) {
        $this->config = $config;
        $this->size = $size;
        $this->pool = new Channel($size);
        $this->connections = [];
        $this->lastUsed = [];
        $this->maxIdleTime = $config['max_idle_time'] ?? 600;
        $this->cleanupInterval = $config['cleanup_interval'] ? $config['cleanup_interval']*1000 : 5000;

        for ($i = 0; $i < $size; $i++) {
            $this->pool->push($i);
            $this->connections[$i] = $this->createConnection();
            $this->lastUsed[$i] = time();
        }

        Timer::tick($this->cleanupInterval, function() {
            $this->cleanup();
        });
    }

    abstract protected function createConnection();

    public function get() {
        $index = $this->pool->pop();
        if ($this->connections[$index] === null) {
            $this->connections[$index] = $this->createConnection();
        }
        $this->lastUsed[$index] = time();
        return [$index, $this->connections[$index]];
    }

    public function put($index) {
        $this->lastUsed[$index] = time();
        $this->pool->push($index);
    }

    public function close() {
        while (!$this->pool->isEmpty()) {
            $index = $this->pool->pop();
            if ($this->connections[$index] !== null) {
                $this->connections[$index]->close();
            }
        }
    }

    protected function cleanup() {
        $now = time();
        foreach ($this->connections as $index => $conn) {
            if ($conn && ($now - $this->lastUsed[$index] > $this->maxIdleTime)) {
                $conn->close();
                $this->connections[$index] = null;
                $this->lastUsed[$index] = $now;
            }
        }
    }
}
