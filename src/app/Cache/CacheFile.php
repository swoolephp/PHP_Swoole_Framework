<?php

namespace App\Cache;

use Swoole\Coroutine\System;

class CacheFile implements Cache
{
    protected $cacheDir;

    public function __construct($config)
    {
        $this->cacheDir = $config['cache'] ?? BASE_PATH . '/writable/cache';
    }

    public function get($key)
    {
        $filename = $this->getFilePath($key);
        if (!is_file($filename)) {
            return null;
        }

        $data = System::readFile($filename);
        if ($data === false) {
            return null;
        }

        $cacheData = unserialize($data);
        if (!$cacheData || !isset($cacheData['expire']) || $cacheData['expire'] < time()) {
            $this->del($key);
            return null;
        }

        return $cacheData['data'];
    }

    public function set($key, $value, $timeout = 300)
    {
        $filename = $this->getFilePath($key);
        $cacheData = serialize([
            'data' => $value,
            'expire' => time() + $timeout,
        ]);

        return System::writeFile($filename, $cacheData);
    }

    public function del($key)
    {
        $filename = $this->getFilePath($key);
        if (is_file($filename)) {
            return unlink($filename);
        }

        return false;
    }

    public function exists($key)
    {
        $filename = $this->getFilePath($key);
        if (is_file($filename)) {
            $data = System::readFile($filename);
            if ($data !== false) {
                $cacheData = unserialize($data);
                if ($cacheData && isset($cacheData['expire']) && $cacheData['expire'] >= time()) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function getFilePath($key)
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }

    public function close()
    {
        // No close operation needed for file-based cache
    }
}
