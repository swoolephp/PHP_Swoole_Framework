<?php
return [
    'db' => [
        'host' => '172.20.0.2',
        'dbname' => 'swooleframework',
        'user' => 'nguoidungswoole',
        'password' => 'MatKhauNgauNhien()^!2024',
        'charset' => 'utf8',
        'port' => 3306
    ],
    'redis' => [
        'host' => '172.20.0.3',
        'port' => 6379,
        'user' => '',  // Nếu không cần user, có thể bỏ qua
        'password' => '',
    ],
    'writable' => [
        'cache' => BASE_PATH . '/writable/cache',
        'data' => BASE_PATH . '/writable/data',
        'logs'  =>  BASE_PATH . '/writable/logs',
    ],
    'anti_ddos' => [
        'enable' => true,
        'rate_limit' => 10,  // 100 requests per minute
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
