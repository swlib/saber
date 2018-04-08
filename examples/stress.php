<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/7 下午6:11
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';
co::set(['max_coroutine' => 8191]);
go(function () {
    $requests = [];
    for ($i = 6666; $i--;) {
        $requests[] = ['uri' => 'http://127.0.0.1'];
    }
    $res = Saber::requests($requests);
    echo "use {$res->time}s\n";
    echo "success: $res->success_num, error: $res->error_num";
});
// on MacOS
// use 0.91531705856323s
// success: 6666, error: 0