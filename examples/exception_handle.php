<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 上午2:17
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    Saber::exceptionHandle(function (\Exception $e) {
        echo get_class($e) . " is caught!";
        return true;
    });
    Saber::get('http://httpbin.org/redirect/10');
});