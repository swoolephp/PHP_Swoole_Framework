<?php

namespace App\Core;

use Swoole\Http\Request;
use Swoole\Http\Response;

class StaticServer {
    protected $config;
    protected $staticDirectories;
    protected $defaultExpireTime;
    protected $mediaConfig;
    protected $assetsConfig;

    public function __construct(array $config) {
        $this->config = $config;
        $this->staticDirectories = $config['directories'];
        $this->defaultExpireTime = $config['default_expire_time'];
        $this->mediaConfig = $config['media'];
        $this->assetsConfig = $config['assets'];
    }

    public function handle(Request $request, Response $response): bool {
        $uri = $request->server['request_uri'];
        
        // Prevent directory traversal attacks
        $uri = $this->sanitizeUri($uri);

        $staticPath = BASE_PATH . '/public' . $uri;
        $fileExtension = strtolower(pathinfo($staticPath, PATHINFO_EXTENSION));

        $isStaticRequest = false;

        // Check if the request is for a static directory
        foreach ($this->staticDirectories as $directory) {
            if (strpos($uri, "/$directory/") === 0) {
                $isStaticRequest = true;
                break;
            }
        }

        // Check if the request is for a static file extension
        if (!$isStaticRequest) {
            $allStaticExtensions = array_merge(
                $this->mediaConfig['extensions'],
                $this->assetsConfig['extensions']
            );

            if (in_array($fileExtension, $allStaticExtensions)) {
                $isStaticRequest = true;
            }
        }

        if ($isStaticRequest && file_exists($staticPath) && is_file($staticPath)) {
            $expireTime = $this->defaultExpireTime;

            if (in_array($fileExtension, $this->mediaConfig['extensions'])) {
                $expireTime = $this->mediaConfig['expire_time'];
            } elseif (in_array($fileExtension, $this->assetsConfig['extensions'])) {
                $expireTime = $this->assetsConfig['expire_time'];
            }

            $response->header("X-Frame-Options", "DENY");
            $response->header("X-Content-Type-Options", "nosniff");
            $response->header("Cache-Control", "public, max-age=$expireTime");
            $response->header("Content-Type", mime_content_type($staticPath));
            // Ensure Content-Disposition is not set, so the browser will display the file
            // $response->header("Content-Disposition", "inline");
            $response->sendfile($staticPath);
            return true;
        }

        // Return 404 for not found static files
        if ($isStaticRequest) {
            $response->status(404);
            $response->header("Content-Type", "text/plain; charset=utf-8");
            $response->end("File not found");
            return true;
        }

        return false;
    }

    //phan nay se con nghien cuu update them. Ae nen custom them khi dung nhe.
    private function sanitizeUri(string $uri): string {
        // Remove any null bytes
        $uri = str_replace("\0", '', $uri);
        
        // Remove any path traversal sequences
        $uri = preg_replace('/\.\.(\/|\\\)/', '', $uri);

        // Sanitize against any other potentially malicious characters
        return filter_var($uri, FILTER_SANITIZE_URL);
    }
}
