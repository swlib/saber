<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/9 下午6:52
 */

use Swlib\Http\ContentType;
use Swlib\Http\SwUploadFile;
use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';
go(function () {
    $file1 = __DIR__ . '/black.png';
    $file2 = [
        'path' => __DIR__ . '/black.png',
        'name' => 'white.png',
        'type' => ContentType::get('png'),
        'offset' => null, //re-upload from break
        'size' => null //upload a part of the file
    ];
    $file3 = new SwUploadFile(
        __DIR__ . '/black.png',
        'white.png',
        ContentType::get('png')
    );

    echo SaberGM::post('http://eu.httpbin.org/post', null, [
            'files' => [
                'image1' => $file1,
                'image2' => $file2,
                'image3' => $file3
            ]
        ]
    );
});
