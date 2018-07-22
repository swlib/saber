<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/8 下午2:53
 */

// swoole version less than or equal 4.0.1
define('SABER_SW_LE_V401', version_compare(swoole_version(), '4.0.1', '<='));
// http client properties need be clear by saber
// ref-count check problem with debug version, and in ver >= 4.0.1 swoole can auto clear
define('SABER_HCP_NEED_CLEAR', !PHP_DEBUG && SABER_SW_LE_V401);

function saber_exit(): bool
{
    swoole_event_exit();
    return true;
}

function saber_pool_release(): bool
{
    Swlib\Saber\ClientPool::getInstance()->releaseAll();
    return true;
}

function saber_pool_get_status($key = null): array
{
    $p = Swlib\Saber\ClientPool::getInstance();
    return $key ? $p->getStatus($key) : $p->getAllStatus(true);
}
