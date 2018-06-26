<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/30 下午12:44
 */

use Swlib\Saber;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $uri = 'http://eu.httpbin.org/basic-auth/foo/bar';
    $res = SaberGM::get(
        $uri, [
            'exception_report' => 0,
            'retry' => function (Saber\Request $request) {
                echo "retry...\n";
                $request->withBasicAuth('foo', 'bar');
            }
        ]
    );

    echo $res;
});
