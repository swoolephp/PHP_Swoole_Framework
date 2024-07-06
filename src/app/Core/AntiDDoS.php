<?php

namespace App\Core;

use Swoole\Table;
use Swoole\Http\Request;
use Swoole\Server;

class AntiDDoS
{
    protected $enable;
    protected $rateLimit;
    protected $blockDurations;
    protected $bannedIPListFile;
    protected $restoreOnRestart;
    protected $requestsTable;
    protected $blockListTable;
    protected $logger;

    public function __construct(array $config, AsyncLogger $logger)
    {
        $this->enable = $config['enable'];
        $this->rateLimit = $config['rate_limit'];
        $this->blockDurations = $config['block_duration'];
        $this->bannedIPListFile = $config['block_list_file'];
        $this->restoreOnRestart = $config['restore_on_restart'];
        $this->logger = $logger;

        // Create Swoole Tables
        $this->requestsTable = new Table(1024);
        $this->requestsTable->column('count', Table::TYPE_INT);
        $this->requestsTable->column('time', Table::TYPE_INT);
        $this->requestsTable->create();

        $this->blockListTable = new Table(1024);
        $this->blockListTable->column('expire', Table::TYPE_INT);
        $this->blockListTable->create();

        if ($this->restoreOnRestart && $this->enable) {
            $this->restoreBannedIPs();
        }
    }

    protected function restoreBannedIPs()
    {
        if (file_exists($this->bannedIPListFile)) {
            $lines = file($this->bannedIPListFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                list($ip, $expire) = explode(':', $line);
                if ((int)$expire > time() || (int)$expire == -1) {
                    $this->blockListTable->set($ip, ['expire' => (int)$expire]);
                }
            }
        }
    }

    public function check(Request $request)
    {
        if (!$this->enable) {
            return ['status' => true];
        }

        $ip = $this->getUserIP($request);
        $currentTime = time();

        // Check if IP is already blocked
        if ($this->blockListTable->exists($ip)) {
            $expire = $this->blockListTable->get($ip, 'expire');
            if ($expire > $currentTime || $expire == -1) {
                return [
                    'status' => false,
                    'remaining' => $expire == -1 ? -1 : $expire - $currentTime
                ];
            } else {
                // Remove expired IP from block list
                $this->requestsTable->set($ip, ['count' => 0, 'time' => $currentTime]);
                $this->blockListTable->del($ip);
                $this->saveBlockList();
            }
        }

        // Count requests within the last minute
        $requestData = $this->requestsTable->get($ip);
        $count = 0;
        $time = $currentTime;

        if ($requestData) {
            $count = $requestData['count'];
            $time = $requestData['time'];
        }

        if (($currentTime - $time) < 60) {
            $count++;
        } else {
            $count = 1;
            $time = $currentTime;
        }

        $this->requestsTable->set($ip, ['count' => $count, 'time' => $time]);

        // Determine if blocking is necessary
        if ($count > $this->rateLimit) {
            if ($count > $this->rateLimit * 7) {
                $level = 'critical';
            } elseif ($count > $this->rateLimit * 5) {
                $level = 'higher';
            } elseif ($count > $this->rateLimit * 3) {
                $level = 'high';
            } elseif ($count > $this->rateLimit * 2) {
                $level = 'medium';
            }elseif ($count > $this->rateLimit) {
                $level = 'low';
            }
            $this->blockIP($ip, $level);
            return [
                'status' => false,
                'remaining' => $this->blockDurations[$level]
            ];
        }

        return ['status' => true];
    }

    protected function getUserIP(Request $request)
    {
        if (!empty($request->header['cf-connecting-ip'])) {
            return $request->header['cf-connecting-ip'];
        } elseif (!empty($request->header['x-forwarded-for'])) {
            $ips = explode(',', $request->header['x-forwarded-for']);
            return trim($ips[0]);
        } elseif (!empty($request->header['x-real-ip'])) {
            return $request->header['x-real-ip'];
        }
        return $request->server['remote_addr'];
    }

    protected function blockIP($ip, $level)
    {
        $currentTime = time();
        $expireTime = $this->blockDurations[$level] == -1 ? -1 : $currentTime + $this->blockDurations[$level];
        $this->blockListTable->set($ip, ['expire' => $expireTime]);
        $this->saveBlockList();

        // Log the IP ban
        $this->logger->error("Blocked IP $ip at level $level until " . ($expireTime == -1 ? 'forever' : date('Y-m-d H:i:s', $expireTime)));
    }

    protected function saveBlockList()
    {
        $data = '';
        foreach ($this->blockListTable as $ip => $info) {
            $data .= "$ip:{$info['expire']}\n";
        }
        file_put_contents($this->bannedIPListFile, $data, LOCK_EX);
    }

    public function applyDelay(Server $server, int $fd)
    {
        if ($fd % 3 === 0) {
            \Swoole\Timer::after(2000, function () use ($server, $fd) {
                $server->confirm($fd);
            });
        } else {
            $server->confirm($fd);
        }
    }
}
