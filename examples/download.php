<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/6/28 下午8:48
 */

use Swlib\SaberGM;

require '../vendor/autoload.php';

go(function () {
    $download_dir = '/tmp/saber.jpg';
    $response = SaberGM::download(
        'https://ws1.sinaimg.cn/large/006DQdzWly1fsr8jt2botj31hc0wxqfs.jpg',
        $download_dir
    );
    if ($response->getSuccess()) {
        exec('open ' . $download_dir);
    }
});
