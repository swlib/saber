<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/23 下午8:50
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $saber = Saber::create([
        'base_uri' => 'http://httpbin.org',
        'headers' => [
            'User-Agent' => null,
            'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
            'DNT' => '1'
        ],
    ]);
    echo $saber->get('/get');
    echo $saber->post('/post');
    echo $saber->patch('/patch');
    echo $saber->put('/put');
    echo $saber->delete('/delete');
});