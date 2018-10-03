<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/14 下午10:58
 */

use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/include/functions.php';
require __DIR__ . '/include/ProcessManager.php';

/** === Travis test need more time === **/
SaberGM::default([
    'timeout' => 30,
    'use_pool' => true
]);
SaberGM::exceptionReport(HttpExceptionMask::E_NONE);
