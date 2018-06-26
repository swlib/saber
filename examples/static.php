<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/26 下午11:21
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    echo "[GET]\n" . SaberGM::get('http://eu.httpbin.org/get')->statusCode . "\n";
    echo "[POST]\n" . SaberGM::post('http://eu.httpbin.org/post')->statusCode . "\n";
    echo "[PUT]\n" . SaberGM::put('http://eu.httpbin.org/put')->statusCode . "\n";
    echo "[PATCH]\n" . SaberGM::patch('http://eu.httpbin.org/patch')->statusCode . "\n";
    echo "[DELETE]\n" . SaberGM::delete('http://eu.httpbin.org/delete')->statusCode . "\n";
});
