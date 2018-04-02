<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/30 下午11:58
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $uri = 'http://t.cn/Rn3tRyK';
    $res = Saber::get($uri);
    var_dump($res->redirect_headers);
});