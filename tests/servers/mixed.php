<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/22 下午5:23
 */

$server = new \swoole_websocket_server($argv[1], $argv[2]);
$server->set(['worker_num' => 1, 'log_file' => '/dev/null']);
$server->on('workerStart', function (\swoole_websocket_server $serv) { });
$server->on('open', function (\swoole_websocket_server $ws, \swoole_http_request $request) {
    $ws->push($request->fd, "server: hello, welcome\n");
});
$server->on('request', function (\swoole_http_request $request, \swoole_http_response $response) {
    $response->end('Hello: ' . $request->rawContent());
});
$server->on('message', function (\swoole_websocket_server $ws, \swoole_websocket_frame $frame) {
    echo "client: {$frame->data}";
    $frame->data = str_replace('server', 'client', $frame->data);
    $ws->push($frame->fd, "server-reply: {$frame->data}");
});
$server->on('close', function (\swoole_websocket_server $ws, int $fd) {
    echo "client-{$fd} is closed\n";
    $ws->shutdown();
});
$server->start();
