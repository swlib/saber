<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/9 下午6:52
 */

use Swlib\Http\ContentType;
use Swlib\Http\SwUploadFile;
use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';
go(function () {
    echo Saber::post('http://httpbin.org/post', null, [
            'files' => [
                //string
                'image1' => __DIR__ . '/black.png',
                //array
                'image2' => [
                    'path' => __DIR__ . '/black.png',
                    'name' => 'white.png',
                    'type' => ContentType::$Map['png']
                ],
                //object
                'image3' => new SwUploadFile(
                    __DIR__ . '/black.png',
                    'white.png',
                    ContentType::$Map['png']
                )
            ]
        ]
    );
});