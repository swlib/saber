<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/31 上午1:13
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    /**@var $queue Saber\Request[] */
    $queue = $res_list = [];
    for ($i = 4; $i--;) {
        $queue[] = Saber::wait()->get('https://github.com/');
    }
    foreach ($queue as $req) {
        $res_list[] = $req->recv()->statusCode;
    }
    var_dump($res_list);
});