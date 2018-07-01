<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/2 上午5:20
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    // the simplest example without `http://` (but you'd better write a full uri)
    echo SaberGM::get('eu.httpbin.org/get');
});
