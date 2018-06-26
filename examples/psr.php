<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/30 上午12:59
 */

use Swlib\Http\BufferStream;
use Swlib\Http\ContentType;
use Swlib\Http\Uri;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $response = SaberGM::psr()
        ->withMethod('POST')
        ->withUri(new Uri('http://eu.httpbin.org/post?foo=bar'))
        ->withQueryParams(['foo' => 'option is higher-level than uri'])
        ->withHeader('content-type', ContentType::JSON)
        ->withBody(new BufferStream(json_encode(['foo' => 'bar'])))
        ->exec()->recv();

    echo $response->getBody();
});
