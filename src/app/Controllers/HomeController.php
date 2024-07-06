<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Database\Database;
use App\Cache\CacheManager;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'viewFile'  =>  'home.php',
            'content'   =>  'Welcome to the Home Page!'
        ];
        $this->view('layout', $data);
    }

    public function redisCacheDemo()
    {
        /*
        $cache = new CacheRedis();

        $key = 'sample_key';
        $value = 'This is a sample value';

        // Set cache
        $cache->set($key, $value, 300);

        // Get cache
        $cachedValue = $cache->get($key);

        echo "Redis Cache Value: " . $cachedValue;
        */
        echo 'redis';
    }

    public function fileCacheDemo()
    {
        /*
        $cache = new CacheFiles();

        $key = 'sample_key';
        $value = 'This is a sample value';

        // Set cache
        $cache->set($key, $value, 300);

        // Get cache
        $cachedValue = $cache->get($key);

        echo "File Cache Value: " . $cachedValue;
        */
        echo 'file';
    }

    public function createTable()
    {
        $db = Database::getInstance('mysql');

        $createTableSQL = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                email VARCHAR(100) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=INNODB;
        ";

        $db->query($createTableSQL);

        echo "Table 'users' created successfully.";
    }

    public function insertData()
    {
        $db = Database::getInstance('mysql');

        $insertSQL = "INSERT INTO users (username, email) VALUES (?, ?)";
        $params = ['john_doe', 'john@example.com'];

        $db->execute($insertSQL, $params);

        echo "Data inserted successfully.";
    }

    public function queryData()
    {
        $cache = CacheManager::getInstance('redis');
        $cache_name = 'query_data';
        $result = $cache->get($cache_name);
        if (!$result){
            $db = Database::getInstance('mysql');

            $selectSQL = "SELECT * FROM users WHERE email = ?";
            $params = ['john@example.com'];

            $result = $db->query($selectSQL, $params);
            if ($result){
                echo 'set cache';
                $cache->set($cache_name, $result, 300);
            }
        }else{
            echo 'get cache';
        }
        foreach ($result as $row) {
            echo "ID: " . $row['id'] . "<br>";
            echo "Username: " . $row['username'] . "<br>";
            echo "Email: " . $row['email'] . "<br>";
            echo "Created At: " . $row['created_at'] . "<br>";
        }
    }
}
