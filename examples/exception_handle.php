<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 上午2:17
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    SaberGM::exceptionHandle(function (\Exception $e) {
        echo get_class($e) . " is caught!";
        return true;
    });
    SaberGM::get('http://eu.httpbin.org/redirect/10');
});
