<?php

namespace App\Database;

use Swoole\Coroutine\PostgreSQL;

class PostgreSQLPool extends DatabasePool {
    protected function createConnection() {
        $pg = new PostgreSQL();
        $conn = $pg->connect([
            'host' => $this->config['host'],
            'port' => $this->config['port'],
            'user' => $this->config['user'],
            'password' => $this->config['password'],
            'database' => $this->config['dbname']
        ]);
        return $conn;
    }

    public function query($sql, $params = []) {
        list($index, $connection) = $this->get();
        $result = $connection->prepare($sql)->execute($params);
        $this->put($index);
        return $result;
    }

    public function list($table, $conditions = [], $offset = 0, $limit = 10, $orderBy = '', $order = 'ASC') {
        $query = "SELECT * FROM $table";
        $params = [];
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', array_map(function ($condition) use (&$params) {
                $params[] = $condition['value'];
                return "{$condition['column']} {$condition['operator']} \$".(count($params));
            }, $conditions));
        }
        if (!empty($orderBy)) {
            $query .= " ORDER BY $orderBy $order";
        }
        $query .= " LIMIT $limit OFFSET $offset";
        return $this->query($query, $params);
    }

    public function row($table, $conditions = [], $orderBy = '', $order = 'ASC') {
        return $this->list($table, $conditions, 0, 1, $orderBy, $order)[0] ?? null;
    }

    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(function ($i) {
            return "\$" . ($i + 1);
        }, array_keys($data)));
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return $this->query($query, array_values($data));
    }

    public function update($table, $data, $conditions) {
        $setPart = implode(', ', array_map(function ($col, $i) {
            return "$col = \$" . ($i + 1);
        }, array_keys($data), array_keys($data)));
        $conditionPart = implode(' AND ', array_map(function ($condition, $i) {
            return "{$condition['column']} {$condition['operator']} \$" . ($i + count($data) + 1);
        }, $conditions, array_keys($conditions)));
        $query = "UPDATE $table SET $setPart WHERE $conditionPart";
        $params = array_merge(array_values($data), array_column($conditions, 'value'));
        return $this->query($query, $params);
    }

    public function delete($table, $conditions) {
        $conditionPart = implode(' AND ', array_map(function ($condition, $i) {
            return "{$condition['column']} {$condition['operator']} \$" . ($i + 1);
        }, $conditions, array_keys($conditions)));
        $query = "DELETE FROM $table WHERE $conditionPart";
        $params = array_column($conditions, 'value');
        return $this->query($query, $params);
    }
}
