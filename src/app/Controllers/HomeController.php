<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Libs\CacheRedis;
use App\Libs\CacheFiles;

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
        $cache = new CacheRedis();

        $key = 'sample_key';
        $value = 'This is a sample value';

        // Set cache
        $cache->set($key, $value, 300);

        // Get cache
        $cachedValue = $cache->get($key);

        echo "Redis Cache Value: " . $cachedValue;
    }

    public function fileCacheDemo()
    {
        $cache = new CacheFiles();

        $key = 'sample_key';
        $value = 'This is a sample value';

        // Set cache
        $cache->set($key, $value, 300);

        // Get cache
        $cachedValue = $cache->get($key);

        echo "File Cache Value: " . $cachedValue;
    }
}
