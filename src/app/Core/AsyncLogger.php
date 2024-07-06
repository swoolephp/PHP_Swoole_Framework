<?php

namespace App\Core;

use Swoole\Coroutine\System;

class AsyncLogger
{
    protected $logPath;

    public function __construct($subPath = '')
    {
        $config = Config::getInstance()->get('writable');
        $baseLogPath = $config['logs'];
        $this->logPath = $baseLogPath . '/' . $subPath . '.log';

        if (!file_exists($baseLogPath)) {
            mkdir($baseLogPath, 0777, true);
        }
    }

    public function log($message)
    {
        $date = date('Y-m-d H:i:s');
        $msg = "[$date] $message\n";
        $this->writeLog($msg);
    }

    public function error($message)
    {
        $this->log("ERROR: $message");
    }

    public function info($message)
    {
        $this->log("INFO: $message");
    }

    protected function writeLog($message)
    {
        System::writeFile($this->logPath, $message, FILE_APPEND | LOCK_EX);
    }
}
