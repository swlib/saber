<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 上午2:16
 */

use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    //redirect exception
    try {
        Saber::get('http://httpbin.org/redirect/10');
    } catch (\Exception$e) {
        echo get_class($e) . " occurs!\n";
    }

    //set report to ignore redirect exception
    Saber::exceptionReport(
        HttpExceptionMask::E_ALL ^ HttpExceptionMask::E_REDIRECT
    );

    Saber::get('http://httpbin.org/redirect/10');
    echo "No exception";
});