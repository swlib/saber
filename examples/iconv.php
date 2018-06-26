<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/5/6 下午12:00
 */

use Swlib\SaberGM;

require '../vendor/autoload.php';

go(function () {
    echo SaberGM::get('http://jtb.cust.edu.cn/tz/', ['iconv' => ['gbk', 'utf-8']]);
});
