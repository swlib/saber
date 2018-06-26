<?php
/**
 * Copyright: Toast Studio
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/6 上午12:04
 */

use Swlib\Saber;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    echo SaberGM::get(
        'http://eu.httpbin.org/redirect-to?url=http://www.twosee.cn', [
            'before_redirect' => function (Saber\Request $request) {
                echo 'redirect to: ' . $request->getUri() . "\n\n";
                return false; //use false ret val to shutdown redirect
            }
        ]
    );
});
