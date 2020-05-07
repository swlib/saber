<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/30 下午11:58
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $uri = 'http://t.cn/Rn3tRyK';
    $res = SaberGM::get($uri);
    var_dump($res->getRedirectHeaders());
});
