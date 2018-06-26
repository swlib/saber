<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/23 下午8:50
 */

use Swlib\Http\ContentType;
use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $saber = Saber::create([
        'base_uri' => 'http://eu.httpbin.org',
        'headers' => [
            'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
            'Content-Type' => ContentType::JSON,
            'DNT' => '1',
            'User-Agent' => null
        ]
    ]);
    echo $saber->get('/get');
    echo $saber->delete('/delete');
    echo $saber->post('/post', ['foo' => 'bar']);
    echo $saber->patch('/patch', ['foo' => 'bar']);
    echo $saber->put('/put', ['foo' => 'bar']);
});
