<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/7 上午12:32
 */

use Swlib\Saber;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    SaberGM::get('http://twosee.cn/', [
        'before' => function (Saber\Request $request) {
            $uri = $request->getUri();
            echo "log: request $uri now...\n";
        },
        'after' => function (Saber\Response $response) {
            if ($response->getSuccess()) {
                echo "log: success!\n";
            } else {
                echo "log: failed\n";
            }
            echo "use {$response->getTime()}s";
        }
    ]);
});
