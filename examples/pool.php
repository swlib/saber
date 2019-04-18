<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/1 下午4:07
 */

use Swlib\Saber;

require __DIR__ . '/../vendor/autoload.php';

go(function () {
    $pool = Saber::create([
        'base_uri' => 'https://www.qq.com',
        'use_pool' => true
    ]);
    $start = microtime(true);
    assert($pool->get('/')->getSuccess(), true);
    assert($pool->get('/contract.shtml')->getSuccess(), true);
    assert($pool->get('/dzwfggcns.htm')->getSuccess(), true);
    $pool_time = microtime(true) - $start;
    var_dump($pool_time);

    $not_pool = Saber::create([
        'base_uri' => 'https://www.qq.com',
        'use_pool' => false
    ]);
    $start = microtime(true);
    assert($not_pool->get('/')->getSuccess(), true);
    assert($not_pool->get('/contract.shtml')->getSuccess(), true);
    assert($not_pool->get('/dzwfggcns.htm')->getSuccess(), true);
    $not_pool_time = microtime(true) - $start;
    var_dump($not_pool_time);

    assert($pool_time < $not_pool_time);

    swoole_event_exit();
});
