<?php
namespace App\Core;

class Application {
    private $router;
    private $logger;

    public function __construct(Router $router, AsyncLogger $logger) {
        $this->router = $router;
        $this->logger = $logger;
    }

    public function handle($request, $response) {
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_SERVER = array_merge($_SERVER, $request->server ?? []);
        $_COOKIE = $request->cookie ?? [];
        $_FILES = $request->files ?? [];
        $_SERVER['REQUEST_URI'] = $request->server['request_uri'] ?? '/';
        $_SERVER['REQUEST_METHOD'] = $request->server['request_method'] ?? 'GET';

        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        return $this->router->dispatch($method, $uri);
    }
}
