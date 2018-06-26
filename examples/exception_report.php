<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 上午2:16
 */

use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    //redirect exception
    try {
        SaberGM::get('http://eu.httpbin.org/redirect/10');
    } catch (\Exception$e) {
        echo get_class($e) . " occurs!\n";
    }

    //set report to ignore redirect exception
    SaberGM::exceptionReport(
        HttpExceptionMask::E_ALL ^ HttpExceptionMask::E_REDIRECT
    );

    SaberGM::get('http://eu.httpbin.org/redirect/10');
    echo "No exception";
});
