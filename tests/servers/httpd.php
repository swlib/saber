<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Maintainer: ihipop <ihipop@gmail.com>
 * Date: 2019年04月23日17:57:10
 */

$http = new Swoole\Http\Server($argv[1], $argv[2]);
$http->set(['worker_num' => 1, 'log_file' => '/dev/null']);
$http->on('request', function ($request, $response) {
    $response->end(json_encode($request,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
});
$http->start();
