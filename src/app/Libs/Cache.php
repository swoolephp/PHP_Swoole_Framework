<?php

namespace App\Libs;

interface Cache
{
    public function get($key);
    public function set($key, $value, $timeout = 300); //500ms
    public function del($key);
    public function exists($key);
}
