<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/1 下午7:56
 */

use Swlib\Http\Exception\RequestException;
use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    try {
        Saber::get('https://www.google.com/', ['timeout' => 2]); //China only
    } catch (RequestException $e) {
        var_dump($e->hasResponse());
        var_dump($e->getMessage());
    }
});