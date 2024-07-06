<?php

namespace App\Cache;

use App\Core\Config;

class CacheManager
{
    protected static $instances = [];

    public static function getInstance($cacheType)
    {
        if (!isset(self::$instances[$cacheType])) {
            $config = Config::getInstance()->get($cacheType);
            switch ($cacheType) {
                case 'redis':
                    self::$instances[$cacheType] = new CacheRedis($config);
                    break;
                case 'file':
                    self::$instances[$cacheType] = new CacheFile($config);
                    break;
                case 'apc':
                    self::$instances[$cacheType] = new CacheAPC($config);
                    break;
                default:
                    throw new \Exception("Unsupported cache type: $cacheType");
            }
        }
        return self::$instances[$cacheType];
    }

    public static function closeAll()
    {
        foreach (self::$instances as $instance) {
            if (method_exists($instance, 'close')) {
                $instance->close();
            }
        }
    }
}

interface Cache
{
    public function get($key);
    public function set($key, $value, $timeout = 300);
    public function del($key);
    public function exists($key);
}
