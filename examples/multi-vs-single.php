<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/31 下午3:36
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    echo str_repeat("=", 20) . "\n";

    $response = SaberGM::request(['uri' => 'https://github.com/']);
    echo "single-request [ status: {$response->statusCode} ]: \n" .
        "consuming-time: {$response->getTime()}s\n";

    echo str_repeat("=", 20) . "\n";

    $responses = SaberGM::requests([
        ['uri' => 'http://github.com/'],
        ['uri' => 'http://github.com/'],
        ['uri' => 'http://github.com/'],
    ]);

    echo
        "multi-requests [ {$responses->success_num} ok, {$responses->error_num} error ]:\n" .
        "consuming-time: {$responses->time}s\n";

    echo str_repeat("=", 20) . "\n";
});
