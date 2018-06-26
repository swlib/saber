<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/6/26 下午9:32
 */

use Swlib\SaberGM;

require_once __DIR__ . '/../vendor/autoload.php';

go(function () {
    $uri = "http://ip.cn";
    $content = (string)SaberGM::get($uri, ['ua' => 'curl/7.54.0'])->getBody();
    echo $content;
});
