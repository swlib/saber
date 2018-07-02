<?php

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

$ws = new swoole_websocket_server('0.0.0.0', 9999);
$ws->set(['worker_num' => 1]);
$ws->on('workerStart', function (swoole_websocket_server $serv) {
    $websocket = SaberGM::websocket('ws://127.0.0.1:9999');
    $i = 5;
    while ($i) {
        echo $websocket->recv() . "\n";
        $websocket->push("hello $i!");
        $i--;
        co::sleep(0.5);
    }
    $serv->shutdown();
});
$ws->on('open', function (swoole_websocket_server $ws, swoole_http_request $request) {
    $ws->push($request->fd, "server: hello, welcome");
});
$ws->on('message', function (swoole_websocket_server $ws, swoole_websocket_frame $frame) {
    echo "client: {$frame->data}\n";
    $ws->push($frame->fd, "server-reply: {$frame->data}");
});
$ws->on('close', function ($ws, $fd) {
    echo "client-{$fd} is closed\n";
});
$ws->start();
