<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/14 下午2:38
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    // max_co is the max number of concurrency request once, it's very useful to prevent server-waf limit.
    $requests = array_fill(0, 10, ['uri' => 'https://www.qq.com/']);
    echo SaberGM::requests($requests, ['max_co' => 5])->time."\n";
    echo SaberGM::requests($requests, ['max_co' => 1])->time."\n";
});
