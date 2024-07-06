<?php
define('BASE_PATH', __DIR__);
define('CORE_PATH', BASE_PATH . '/app/Core');

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Runtime;
use Swoole\Table;
use Swoole\Coroutine;
use Swoole\Coroutine\MySQL;
use Swoole\Coroutine\Redis;

Runtime::enableCoroutine();

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';

// Server options
$serverOptions = [
    'worker_num' => swoole_cpu_num(),
    'max_coroutine' => 1000 * swoole_cpu_num(),
    'hook_flags' => SWOOLE_HOOK_ALL,
    'daemonize' => false,
    'http_compression' => true,
    'http_compression_level' => 5,
    'max_conn' => 2000,
    'max_request' => 5000,
    'open_tcp_nodelay' => true,
];

$server = new Server("0.0.0.0", 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
$server->set($serverOptions);

$poolSize = $config['cache']['pool_size'];

// Initialize Swoole Table for Redis and MySQL connection management
$redisTable = new Table($poolSize);
$redisTable->column('status', Table::TYPE_INT);
$redisTable->create();

$mysqlTable = new Table($poolSize);
$mysqlTable->column('status', Table::TYPE_INT);
$mysqlTable->create();

$redisConnections = [];
$mysqlConnections = [];

$server->on("start", function (Server $server) {
    echo "Swoole server is started at http://127.0.0.1:9501\n";
});

$server->on("workerStart", function (Server $server, int $workerId) use ($redisTable, $mysqlTable, $poolSize, $config, &$redisConnections, &$mysqlConnections) {
    Coroutine::create(function () use ($redisTable, $mysqlTable, $poolSize, $config, &$redisConnections, &$mysqlConnections) {
        for ($i = 0; $i < $poolSize; $i++) {
            if (!isset($redisConnections[$i])) {
                $redis = new Redis();
                $redis->connect($config['redis']['host'], $config['redis']['port']);
                if (!empty($config['redis']['password'])) {
                    $redis->auth($config['redis']['password']);
                }
                $redisConnections[$i] = $redis;
                $redisTable->set($i, ['status' => 1]);
            }

            if (!isset($mysqlConnections[$i])) {
                $mysql = new MySQL();
                $mysql->connect([
                    'host' => $config['mysql']['host'],
                    'port' => $config['mysql']['port'],
                    'user' => $config['mysql']['user'],
                    'password' => $config['mysql']['password'],
                    'database' => $config['mysql']['dbname'],
                    'charset' => $config['mysql']['charset'],
                ]);
                $mysqlConnections[$i] = $mysql;
                $mysqlTable->set($i, ['status' => 1]);
            }
        }
    });
});

$server->on("request", function (Request $request, Response $response) use ($redisTable, $mysqlTable, $server, &$redisConnections, &$mysqlConnections) {
    Coroutine::create(function () use ($request, $response, $redisTable, $mysqlTable, $server, &$redisConnections, &$mysqlConnections) {
        $workerId = $server->worker_id;
        $response->header("Content-Type", "text/html; charset=utf-8");
        $response->header("X-Worker-ID", $workerId);

        // Get Redis connection
        $redisIndex = -1;
        for ($i = 0; $i < $redisTable->count(); $i++) {
            if ($redisTable->get($i, 'status') == 1) {
                $redisIndex = $i;
                $redisTable->set($i, ['status' => 0]);
                break;
            }
        }

        // Get MySQL connection
        $mysqlIndex = -1;
        for ($i = 0; $i < $mysqlTable->count(); $i++) {
            if ($mysqlTable->get($i, 'status') == 1) {
                $mysqlIndex = $i;
                $mysqlTable->set($i, ['status' => 0]);
                break;
            }
        }

        try {
            if ($redisIndex != -1 && $mysqlIndex != -1) {
                $redis = $redisConnections[$redisIndex];
                $mysql = $mysqlConnections[$mysqlIndex];

                $cacheKey = 'data_key';
                $cachedData = $redis->get($cacheKey);

                if ($cachedData) {
                    $response->end("Data from Redis (Worker {$workerId}): " . $cachedData);
                } else {
                    $result = $mysql->query("SELECT * FROM users LIMIT 9999");
                    if ($result) {
                        $data = json_encode($result);
                        $redis->set($cacheKey, $data, 300); // Cache for 300 seconds
                        $response->end("Data from MySQL (Worker {$workerId}): " . $data);
                    } else {
                        $response->status(200);
                        $response->end("Internal Server Error: Unable to fetch data from MySQL (Worker {$workerId}).");
                    }
                }
            } else {
                $response->status(200);
                $response->end("Internal Server Error: No available connections (Worker {$workerId}).");
            }
        } finally {
            if ($redisIndex != -1) {
                $redisTable->set($redisIndex, ['status' => 1]);
            }
            if ($mysqlIndex != -1) {
                $mysqlTable->set($mysqlIndex, ['status' => 1]);
            }
        }
    });
});

$server->start();
