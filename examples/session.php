<?php
/**
 * Copyright: Toast Studio
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/3 下午3:59
 */

use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $session = Saber::session([
        'base_uri' => 'http://eu.httpbin.org',
        // 'redirect' => 0,
        'exception_report' => HttpExceptionMask::E_ALL ^ HttpExceptionMask::E_REDIRECT
    ]);
    $session->get('/cookies/set?foo=bar&k=v&apple=banana');
    $session->get('/cookies/delete?k');
    echo $session->get('/cookies')->body;
});
