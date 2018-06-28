<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/14 下午10:58
 */

require __DIR__.'/../vendor/autoload.php';

//travis test need more time
Swlib\SaberGM::default([
    'timeout' => 30
]);
