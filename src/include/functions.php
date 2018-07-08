<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/8 下午2:53
 */

function saber_exit()
{
    swoole_event_exit();
}

function saber_pool_release()
{
    Swlib\Saber\ClientPool::getInstance()->destroy();
}

function saber_pool_get_status(): array
{
    return Swlib\Saber\ClientPool::getInstance()->getStatus();
}
