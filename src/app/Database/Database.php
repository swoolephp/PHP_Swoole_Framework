<?php

namespace App\Database;

use App\Core\Config;

class Database {
    protected static $instances = [];
    protected $pool;

    protected function __construct($dbType, $poolSize = 10) {
        $config = Config::getInstance()->get($dbType);
        switch ($dbType) {
            case 'scylladb':
                $this->pool = new ScyllaDBPool($config, $poolSize);
                break;
            case 'mysql':
                $this->pool = new MySQLPool($config, $poolSize);
                break;
            case 'postgresql':
                $this->pool = new PostgreSQLPool($config, $poolSize);
                break;
            default:
                throw new \Exception("Unsupported database type: $dbType");
        }
    }

    public static function getInstance($dbType, $poolSize = 10) {
        if (!isset(self::$instances[$dbType])) {
            self::$instances[$dbType] = new self($dbType, $poolSize);
        }
        return self::$instances[$dbType];
    }

    public static function closeAll() {
        foreach (self::$instances as $instance) {
            $instance->close();
        }
    }

    public function query($sql, $params = []) {
        return $this->pool->query($sql, $params);
    }

    public function execute($sql, $params = []) {
        return $this->pool->query($sql, $params);
    }

    public function list($table, $conditions = [], $offset = 0, $limit = 10, $orderBy = '', $order = 'ASC') {
        return $this->pool->list($table, $conditions, $offset, $limit, $orderBy, $order);
    }

    public function row($table, $conditions = [], $orderBy = '', $order = 'ASC') {
        return $this->pool->row($table, $conditions, $orderBy, $order);
    }

    public function insert($table, $data) {
        return $this->pool->insert($table, $data);
    }

    public function update($table, $data, $conditions) {
        return $this->pool->update($table, $data, $conditions);
    }

    public function delete($table, $conditions) {
        return $this->pool->delete($table, $conditions);
    }

    public function close() {
        $this->pool->close();
    }
}
