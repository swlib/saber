<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/7 下午6:11
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

error_reporting(0);
co::set(['max_coroutine' => 8191]);
$http = new swoole_http_server('127.0.0.1', 1234, SWOOLE_BASE);
$http->set([
    'log_file' => '/dev/null',
    'worker_num' => swoole_cpu_num() * 2,
    'enable_coroutine' => false
]);
$http->on('request', function (swoole_http_request $request, swoole_http_response $response) {
    $response->end('<h1>Hello Swoole!</h1>');
});
$http->on('workerStart', function (swoole_server $server, int $worker_id) {
    if ($worker_id === 1) {
        go(function () use ($server) {
            SaberGM::default(['use_pool' => true]);
            $requests = array_fill(0, 6666, ['uri' => 'http://127.0.0.1:1234']);
            $res = SaberGM::requests($requests, ['max_co' => 1111]);
            echo "use {$res->time}s\n";
            echo "success: $res->success_num, error: $res->error_num\n";
            // on MacOS
            // use 0.91531705856323s
            // success: 6666, error: 0
            saber_pool_release();
            $server->shutdown();
        });
    }
});
$http->start();
