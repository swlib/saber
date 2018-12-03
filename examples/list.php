<?php
/**
 * Copyright: Toast Studio
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/14 下午4:55
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $res = SaberGM::list([
        'uri' => [
            'https://www.qq.com/',
            'https://www.baidu.com/',
            'https://www.swoole.com/',
            'http://eu.httpbin.org/'
        ]
    ]);
    echo "success: $res->success_num, error: $res->error_num";
});
