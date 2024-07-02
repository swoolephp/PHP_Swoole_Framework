<?php
define('BASE_PATH', __DIR__);
define('CORE_PATH', BASE_PATH . '/app/Core');
define('VIEW_PATH', BASE_PATH . '/app/Views');

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Runtime;

require_once __DIR__ . '/vendor/autoload.php';
use App\Core\Config;
use App\Core\StaticServer;
use App\Core\AsyncLogger;
use App\Core\AntiDDoS;
use App\Core\Router;
use App\Core\Application;

Runtime::enableCoroutine();
$config = Config::getInstance()->get();

// Prepare SSL configuration only if SSL is enabled in the config
$serverOptions = [
    'worker_num' => swoole_cpu_num(), // Số lượng worker process or số lượng CPU, tăng giá trị này để xử lý nhiều yêu cầu đồng thời hơn. Hàm này đang là auto
    'hook_flags' => SWOOLE_HOOK_ALL, // convert 1 số hàm php thông thường sang PHP SWOOLE để tận dụng Nonblocking IO. SWOOLE_HOOK_ALL or ds sau: SWOOLE_HOOK_TCP, SWOOLE_HOOK_UDP, SWOOLE_HOOK_UNIX, SWOOLE_HOOK_UDG, SWOOLE_HOOK_SSL, SWOOLE_HOOK_TLS, SWOOLE_HOOK_STREAM_FUNCTION, SWOOLE_HOOK_FILE, SWOOLE_HOOK_SLEEP, SWOOLE_HOOK_PROC, SWOOLE_HOOK_CURL, SWOOLE_HOOK_BLOCKING_FUNCTION, SWOOLE_HOOK_ALL
    'daemonize' => false, // Đặt thành true nếu muốn server chạy dưới dạng background daemon (chế độ chạy thực tế)
];
if ($config['ssl']['enable']) {
    $serverOptions['ssl_cert_file'] = $config['ssl']['cert_file'];
    $serverOptions['ssl_key_file'] = $config['ssl']['key_file'];
    $serverOptions['open_http2_protocol'] = true; // Enable HTTP2 if SSL is enabled
}
$server = new Server("0.0.0.0", 9501, SWOOLE_PROCESS, $config['ssl']['enable'] ? SWOOLE_SOCK_TCP | SWOOLE_SSL : SWOOLE_SOCK_TCP);
$server->set($serverOptions);

// Khởi tạo các module
//+ Module Server load file tĩnh
$staticServer = new StaticServer($config['static']);
//+ Module Logger
$logger = new AsyncLogger('system'); //logs vào logs/system.log
if ($config['anti_ddos']['enable']){
    $config['anti_ddos']['block_list_file'] = $config['writable']['data'].'/IP_BANNED.block';
    $antiDDoS = new AntiDDoS($config['anti_ddos'], $logger); //init Load AntiDdos One Time (Fixed IO)
}else{
    null;
}
//+ Module Router
$config_router = require BASE_PATH . '/config/router.php';
$router = new Router($config_router, $logger);
//+ Module Chính Application
$application = new Application($router, $logger);

$server->on("start", function (Server $server) {
    echo "Swoole server is started at http://127.0.0.1:9501\n";
});

$server->on('connect', function (Server $server, int $fd, int $reactorId) use ($config, $antiDDoS) {
    if ($config['anti_ddos']['enable']){
        $antiDDoS->applyDelay($server, $fd);
    }
});

$server->on("request", function (Request $request, Response $response) use ($config, $antiDDoS, $staticServer, $application) {
    if ($staticServer->handle($request, $response)) {
        return;
    }
    //ko ai di check ddos tep tinh nen bo qua khuc nay hehe.
    if ($config['anti_ddos']['enable']) {
        $ddosCheck = $antiDDoS->check($request);
        if (!$ddosCheck['status']) {
            $remainingTime = $ddosCheck['remaining'];
            $response->status(429);
            $response->header("Retry-After", $remainingTime);
            $response->end("Too Many Requests. Try again in $remainingTime seconds.");
            return;
        }
    }

    Swoole\Coroutine::create(function () use ($request, $response, $application){
        ob_start();
        try {
            //$result = $application->handle($request, $response);
            $application->handle($request, $response);
            $result = ob_get_clean();
            $response->header("Content-Type", "text/html; charset=utf-8");
            $response->end($result);
        } catch (\Throwable $e) {
            ob_end_clean();
            $response->status(500);
            $response->header("Content-Type", "text/plain; charset=utf-8");
            $response->end("Internal Server Error: " . $e->getMessage());
        }
    });
});

$server->start();
