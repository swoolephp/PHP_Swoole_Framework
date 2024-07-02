<?php
namespace App\Libs;

use PDO;
use PDOException;
use Swoole\Coroutine\Channel;

class DatabasePool
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
            $pdo = $this->createConnection();
            $this->pool->push($pdo);
        }
    }

    public static function getInstance($size = 10)
    {
        if (self::$instance === null) {
            self::$instance = new self($size);
        }
        return self::$instance;
    }

    protected function createConnection()
    {
        $dsn = "mysql:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['dbname']};charset={$this->config['charset']}";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, $this->config['user'], $this->config['password'], $options);
        } catch (PDOException $e) {
            throw new \Exception("Failed to connect to MySQL: " . $e->getMessage());
        }

        return $pdo;
    }

    public function get()
    {
        return $this->pool->pop();
    }

    public function put($pdo)
    {
        $this->pool->push($pdo);
    }

    public function close()
    {
        while (!$this->pool->isEmpty()) {
            $pdo = $this->pool->pop();
            $pdo = null;  // Close the PDO connection
        }
    }
}
