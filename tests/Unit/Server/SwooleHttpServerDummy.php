<?php

declare(strict_types=1);

namespace K911\Swoole\Tests\Unit\Server;

use Swoole\Http\Server;

class SwooleHttpServerDummy extends Server
{
    public function __construct()
    {
    }

    public function setGlobal($flag, $request_flag = 0): void
    {
    }

    public function on($event, $callback): void
    {
    }

    public function set(array $setting): void
    {
    }

    public function start(): void
    {
    }

    public function send($fd, $data, $from_id = 0): void
    {
    }

    public function sendto($ip, $port, $data, $server_socket = -1): void
    {
    }

    public function sendwait($fd, $send_data): void
    {
    }

    public function close($fd, $reset = false): void
    {
    }

    public function taskwait($task_data, $timeout = 0.5, $dst_worker_id = -1): void
    {
    }

    public function task($data, $dst_worker_id = -1, $callback = null): void
    {
    }

    public function finish($data): void
    {
    }

    public function sendMessage($message, $dst_worker_id = -1): void
    {
    }

    public function heartbeat($if_close_connection = true): void
    {
    }

    public function connection_info($fd, $from_id = -1, $ignore_close = false): void
    {
    }

    public function connection_list($start_fd = -1, $pagesize = 10): void
    {
    }

    public function reload(): void
    {
    }

    public function stop($worker_id = -1, $waitEvent = false): void
    {
    }

    public function shutdown(): void
    {
    }

    public function addListener($host, $port, $type = SWOOLE_SOCK_TCP): void
    {
    }

    public function stats(): void
    {
    }

    public function after($after_time_ms, $callback_function, $param = null): void
    {
    }

    public function listen($host, $port, $type = SWOOLE_SOCK_TCP): void
    {
    }

    public function addProcess($process): void
    {
    }

    public function addtimer($interval): void
    {
    }

    public function deltimer($interval): void
    {
    }

    public function tick($interval_ms, $callback, $param = null): void
    {
    }

    public function clearTimer($id): void
    {
    }

    public function handler($event_name, $event_callback_function): void
    {
    }

    public function sendfile($fd, $filename, $offset = 0, $length = 0): void
    {
    }

    public function bind($fd, $uid): void
    {
    }

    public function getSocket($port = 0): void
    {
    }

    public function exist($fd): void
    {
    }

    public function pause($fd): void
    {
    }

    public function resume($fd): void
    {
    }

    public function defer($callback): void
    {
    }

    public function getClientInfo($fd, $reactor_id = null): void
    {
    }

    public function taskWaitMulti(array $tasks, $timeout = null): void
    {
    }
}
