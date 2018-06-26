<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/27 下午5:40
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $auth = [
        'username' => md5(openssl_random_pseudo_bytes(6)),
        'password' => md5(openssl_random_pseudo_bytes(6))
    ];
    echo SaberGM::get(
        "http://eu.httpbin.org/basic-auth/{$auth['username']}/{$auth['password']}",
        ['auth' => $auth]
    );
});
