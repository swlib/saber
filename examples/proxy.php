<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/31 上午11:37
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $uri = 'http://myip.ipip.net/';
    echo SaberGM::get($uri, ['proxy' => 'http://127.0.0.1:1087'])->body;
    echo SaberGM::get($uri, ['proxy' => 'socks5://127.0.0.1:1086'])->body;
});
