<?php

use Swlib\SaberGM;

require_once __DIR__ . '/../vendor/autoload.php';

go(function () {
    echo "Input the ip your want to check: ";
    $ip = trim(fgets(STDIN));
    $uri = "http://www.ip138.com/ips1388.asp?ip={$ip}&action=2";
    $content = (string)SaberGM::get($uri, ['iconv' => ['gbk', 'utf-8']])->getBody();
    preg_match_all('/<li>(.+?)<\/li>/', $content, $matches);
    echo implode("\n", $matches[1]);
});
