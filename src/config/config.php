<?php
return [
    'scylladb' => [
        'host' => 'localhost',
        'port' => 9042,
        'dbname' => 'your_keyspace',
        'user' => 'your_username',
        'password' => 'your_password',
        'max_idle_time' => 3000,
        'cleanup_interval' => 5 //5000 ms
    ],
    'mysql' => [
        'host' => '172.19.0.3',
        'port' => 3306,
        'dbname' => 'swooleframework',
        'user' => 'nguoidungswoole',
        'password' => 'MatKhauNgauNhien()^!2024',
        'charset' => 'utf8mb4',
        'max_idle_time' => 300,
        'cleanup_interval' => 5 //5000 ms
    ],
    'postgresql' => [
        'host' => 'localhost',
        'port' => 5432,
        'dbname' => 'your_database',
        'user' => 'your_user',
        'password' => 'your_password',
        'charset' => 'UTF8', // Set charset here
        'max_idle_time' => 300,
        'cleanup_interval' => 5 //5000 ms
    ],


    'writable' => [
        'cache' => BASE_PATH . '/writable/cache',
        'data' => BASE_PATH . '/writable/data',
        'logs'  =>  BASE_PATH . '/writable/logs',
    ],
    'cache' => [
        'driver' => 'redis', // file, redis, apc
        'pool_size' => 10,
        'max_idle_time'  =>  300,
        'cleanup_interval' => 5 //5000 ms
    ],
    'redis' => [
        'host' => '172.19.0.2',
        'port' => 6379,
        'user' => '',  // Nếu không cần user, có thể bỏ qua
        'password' => '',
    ],
    
    'anti_ddos' => [
        'enable' => false,
        'rate_limit' => 100,  // 100 requests per minute
        'block_duration' => [  // Duration in seconds
            'low' => 10,       // 10 seconds
            'medium' => 60,    // 1 minute
            'high' => 600,     // 10 minutes
            'higher' => 3600,  // 1 hour
            'critical' => 86400,// 1 day
            'permanent' => -1  // Permanent block
        ],
        'restore_on_restart' => true,
    ],
    'ssl' => [
        'enable' => false,
        'cert_file' => '/www/server/swoole/ssl/server.crt',
        'key_file' => '/www/server/swoole/ssl/server.key'
    ],
    'static' => [
        'default_expire_time' => 86400,  // 1 ngày
        'media' => [
            'extensions' => [
                'jpeg', 'jpg', 'png', 'gif', 'webp', 'ico', 'svg',  // Hình ảnh
                'mp3', 'ogg', 'wav',  // Âm thanh
                'mp4', 'webm', 'ogg'  // Video
            ],
            'expire_time' => 2592000  // 30 ngày
        ],
        'assets' => [
            'extensions' => [
                'css', 'js', 'json',  // CSS và JS
                'woff2', 'woff', 'ttf', 'eot', 'otf'  // Phông chữ
            ],
            'expire_time' => 2592000  // 30 ngày
        ],
        'directories' => [
            'public',
            'asset',
            'assets',
            'upload',
            'uploads',
            'wp-content/uploads'
        ]
    ],
];
