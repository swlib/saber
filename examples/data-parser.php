<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/15 上午12:09
 */

use Swlib\SaberGM;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    [$json, $xml, $html] = SaberGM::list([
        'uri' => [
            'http://eu.httpbin.org/get',
            'http://www.w3school.com.cn/example/xmle/note.xml',
            'http://eu.httpbin.org/html'
        ]
    ]);
    var_dump($json->getParsedJsonArray());
    var_dump($json->getParsedJsonObject());
    var_dump($xml->getParsedXmlObject());
    var_dump($html->getParsedDomObject()->getElementsByTagName('h1')->item(0)->textContent);
    var_dump($html->getDataRegexMatch('/<(?<x>(?#it\'s the show)h1>)(?<title>[^>]+)<\/\k<x>/', 'title'));
});
