<?php
namespace App\Libs;
use App\Core\Config;
use App\Libs\DatabasePool;

class Database
{
    protected $pool;
    protected $stmt;

    public function __construct($poolSize = 10)
    {
        $config = Config::getInstance()->get('db');
        $this->pool = DatabasePool::getInstance($config, $poolSize);
    }

    public function query($sql)
    {
        $pdo = $this->pool->get();
        $this->stmt = $pdo->prepare($sql);
        $this->pool->put($pdo);
    }

    public function bind($param, $value, $type = null)
    {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = \PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = \PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = \PDO::PARAM_NULL;
                    break;
                default:
                    $type = \PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
    }

    public function execute()
    {
        return $this->stmt->execute();
    }

    public function list($table, $fields = '*', $condition = '1=1', $params = [], $orderby = null, $sc = 'ASC', $limit = null, $offset = null)
    {
        $sql = "SELECT {$fields} FROM {$table} WHERE {$condition}";
        if ($orderby) {
            $sql .= " ORDER BY {$orderby} {$sc}";
        }
        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }
        if ($offset) {
            $sql .= " OFFSET {$offset}";
        }

        $this->query($sql);
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        $this->execute();
        return $this->stmt->fetchAll();
    }

    public function row($table, $fields = '*', $condition = '1=1', $params = [], $orderby = null, $sc = 'ASC')
    {
        $sql = "SELECT {$fields} FROM {$table} WHERE {$condition}";
        if ($orderby) {
            $sql .= " ORDER BY {$orderby} {$sc}";
        }
        $sql .= " LIMIT 1";

        $this->query($sql);
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        $this->execute();
        return $this->stmt->fetch();
    }

    public function insert($table, $data)
    {
        if (!isset($data) || !$data) return false;
        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $this->query($sql);
        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }
        return $this->execute();
    }

    public function update($table, $data, $condition, $params = [])
    {
        if (!isset($condition) || !$condition) return false;
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        $setClause = implode(", ", $setClause);

        $sql = "UPDATE {$table} SET {$setClause} WHERE {$condition}";

        $this->query($sql);
        foreach ($data as $key => $value) {
            $this->bind(":$key", $value);
        }
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        return $this->execute();
    }

    public function delete($table, $condition, $params = [])
    {
        if (!isset($condition) || !$condition) return false;
        $sql = "DELETE FROM {$table} WHERE {$condition}";

        $this->query($sql);
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        return $this->execute();
    }

    public function count($table, $field = '*', $condition = '1=1', $params = [])
    {
        $sql = "SELECT COUNT({$field}) AS total FROM {$table} WHERE {$condition}";
        $this->query($sql);
        foreach ($params as $key => $value) {
            $this->bind($key, $value);
        }
        $this->execute();
        $result = $this->stmt->fetch();
        return $result['total'];
    }

    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    public function id()
    {
        $pdo = $this->pool->get();
        $lastInsertId = $pdo->lastInsertId();
        $this->pool->put($pdo);
        return $lastInsertId;
    }

    public function beginTransaction()
    {
        $pdo = $this->pool->get();
        $pdo->beginTransaction();
        $this->pool->put($pdo);
    }

    public function endTransaction()
    {
        $pdo = $this->pool->get();
        $pdo->commit();
        $this->pool->put($pdo);
    }

    public function cancelTransaction()
    {
        $pdo = $this->pool->get();
        $pdo->rollBack();
        $this->pool->put($pdo);
    }

    public function debug()
    {
        return $this->stmt->debugDumpParams();
    }

    public function close()
    {
        $this->pool->close();
    }
}
