<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/2 上午12:01
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $cookies = SaberGM::get('https://github.com/')->cookies;
    var_dump($cookies->toRequestString()); //all cookies
    echo "\ncookie `_gh_sess` is discarded because its domain is `github.com` (hostonly)\n";
    var_dump($cookies->toRequestString('https://help.github.com/')); // this domain
});
