<?php

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

$ws = new swoole_websocket_server('0.0.0.0', 9999);
$ws->set([
    'reactor_num' => 1,
    'worker_num' => 1,
    'dispatch_mode' => 1
]);
$ws->on('workerStart', function () {
    $websocket = Saber::websocket('ws://127.0.0.1:9999');
    while (true) {
        echo $websocket->recv(1) . "\n";
        $websocket->push("hello");
        co::sleep(1);
    }
});
$ws->on('open', function (swoole_websocket_server $ws, swoole_http_request $request) {
    $ws->push($request->fd, "server: hello, welcome\n");
});
$ws->on('message', function (swoole_websocket_server $ws, swoole_websocket_frame $frame) {
    echo "client: {$frame->data}\n";
    $ws->push($frame->fd, "server-reply: {$frame->data}");
});
$ws->on('close', function ($ws, $fd) {
    echo "client-{$fd} is closed\n";
});
$ws->start();