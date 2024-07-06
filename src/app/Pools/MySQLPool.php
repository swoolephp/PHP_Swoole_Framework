<?php

namespace App\Pools;

use Swoole\Coroutine\Channel;
use Swoole\Coroutine\MySQL;

class MySQLPool {
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
            $mysql = new MySQL();
            $connected = $mysql->connect([
                'host' => $this->config['host'],
                'port' => $this->config['port'],
                'user' => $this->config['user'],
                'password' => $this->config['password'],
                'database' => $this->config['dbname'],
                'charset' => $this->config['charset'],
            ]);
            if (!$connected) {
                throw new \Exception("Failed to connect to MySQL");
            }
            return $mysql;
        }
        return $this->pool->pop();
    }

    public function put($mysql) {
        if ($this->pool->isFull()) {
            $mysql->close();
        } else {
            $this->pool->push($mysql);
        }
    }

    public function close() {
        while (!$this->pool->isEmpty()) {
            $mysql = $this->pool->pop();
            $mysql->close();
        }
    }
}
