<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/22 下午5:23
 */

$http = new Swoole\Http\Server($argv[1], $argv[2]);
$http->set(['worker_num' => 2, 'log_file' => '/dev/null']);
$http->on('request', function ($request, $response) {
    $response->end(json_encode($request,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
});
$http->start();
