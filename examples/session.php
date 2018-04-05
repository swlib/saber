<?php
/**
 * Copyright: Toast Studio
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/3 下午3:59
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $session = Saber::session(['base_uri' => 'http://httpbin.org']);
    $session->get('/cookies/set?foo=bar&k=v&apple=banana');
    $session->get('/cookies/delete?k');
    echo $session->get('/cookies')->body;
});