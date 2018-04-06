<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/27 上午1:10
 */

namespace Swlib;

use Swlib\Saber\Client;
use Swlib\Saber\Request;
use Swlib\Saber\ResponseMap;

class Saber
{
    private static function getDefaultClient(bool $renew = false)
    {
        static $defaultClient;
        if ($renew && isset($defaultClient)) {
            unset($defaultClient);
        }

        return $defaultClient ?? $defaultClient = Client::create();
    }

    public static function create(array $options = [])
    {
        return Client::create($options);
    }

    public static function session(array $options = [])
    {
        return Client::session($options);
    }

    public static function psr(array $options = []): Request
    {
        return self::getDefaultClient()->psr($options);
    }

    /** @return \Swlib\Saber\Client */
    public static function wait(array $options = []): Client
    {
        return self::getDefaultClient()->wait();
    }

    public static function request(array $options = [])
    {
        return self::getDefaultClient()->request($options);
    }

    public static function requests(array $requests, array $default_options = []): ResponseMap
    {
        return self::getDefaultClient()->requests($requests, $default_options);
    }

    /**
     * Note: Swoole doesn't support use coroutine in magic methods now
     * To be on the safe side, we removed __call and __callStatic instead of handwriting
     */
    public static function get(string $uri, array $options = [])
    {
        return self::getDefaultClient()->get($uri, $options);
    }

    public static function delete(string $uri, array $options = [])
    {
        return self::getDefaultClient()->delete($uri, $options);
    }

    public static function head(string $uri, array $options = [])
    {
        return self::getDefaultClient()->head($uri, $options);
    }

    public static function post(string $uri, $data = null, array $options = [])
    {
        return self::getDefaultClient()->post($uri, $data, $options);
    }

    public static function put(string $uri, $data = null, array $options = [])
    {
        return self::getDefaultClient()->put($uri, $data, $options);
    }

    public static function patch(string $uri, $data = null, array $options = [])
    {
        return self::getDefaultClient()->patch($uri, $data, $options);
    }

    public static function default(array $options)
    {
        Client::setDefaultOptions($options);
        self::getDefaultClient(true);
    }

    public static function errorReport(int $level)
    {
        self::default(['error_report' => $level]);
    }

}