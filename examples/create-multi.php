<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/5 上午3:41
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $saber = Saber::create(['base_uri' => 'http://eu.httpbin.org']);
    echo $saber->requests([
        ['get', '/get'],
        ['delete', '/delete'],
        ['post', '/post', ['foo' => 'bar']],
        ['patch', '/patch', ['foo' => 'bar']],
        ['put', '/put', ['foo' => 'bar']],
    ]);
});
