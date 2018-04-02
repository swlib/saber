<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/26 下午11:21
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    echo "[GET]\n" . Saber::get('http://httpbin.org/get')->statusCode . "\n";
    echo "[POST]\n" . Saber::post('http://httpbin.org/post')->statusCode . "\n";
    echo "[PUT]\n" . Saber::put('http://httpbin.org/put')->statusCode . "\n";
    echo "[PATCH]\n" . Saber::patch('http://httpbin.org/patch')->statusCode . "\n";
    echo "[DELETE]\n" . Saber::delete('http://httpbin.org/delete')->statusCode . "\n";
});