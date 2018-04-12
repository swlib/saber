<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/12 下午9:58
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    //it's the way to bind some special request data to response.
    $responses = Saber::requests([
        ['uri' => 'http://www.qq.com/', 'mark' => 'it is request one!'],
        ['uri' => 'http://www.qq.com']
    ]);
    echo $responses[0]->getSpecialMark();
});