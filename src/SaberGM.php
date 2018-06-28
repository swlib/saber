<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/27 上午1:10
 */

namespace Swlib;

use Swlib\Saber\Request;
use Swlib\Saber\ResponseMap;

class SaberGM
{
    private static $defaultClient;

    private static function getDefaultClient(): Saber
    {
        return self::$defaultClient ?? self::$defaultClient = Saber::create();
    }

    public static function psr(array $options = []): Request
    {
        return self::getDefaultClient()->psr($options);
    }

    /** @return \Swlib\Saber\Saber */
    public static function wait(): Saber
    {
        return self::getDefaultClient()->wait();
    }

    /******************************************************************************
     *                             Request Methods                                *
     ******************************************************************************/

    /**
     * Note: Swoole <=4 doesn't support use coroutine in magic methods now
     * To be on the safe side, we removed __call and __callStatic instead of handwriting
     */

    public static function request(array $options = [])
    {
        return self::getDefaultClient()->request($options);
    }

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

    public static function options(string $uri, array $options = [])
    {
        return self::getDefaultClient()->options($uri, $options);
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

    public static function download(string $uri, string $dir, int $offset = 0, array $options = [])
    {
        return self::getDefaultClient()->download($uri, $dir, $offset, $options);
    }

    /** @return Saber\Response[]|ResponseMap */
    public static function requests(array $requests, array $default_options = []): ResponseMap
    {
        return self::getDefaultClient()->requests($requests, $default_options);
    }

    /** @return Saber\Response[]|ResponseMap */
    public static function list(array $options, array $default_options = []): ResponseMap
    {
        return self::getDefaultClient()->list($options, $default_options);
    }

    public static function websocket(string $uri)
    {
        return self::getDefaultClient()->websocket($uri);
    }

    /******************************************************************************
     *                             Global Options                                 *
     ******************************************************************************/

    public static function default(array $options = null): ?array
    {
        if ($options === null) {
            return Saber::getDefaultOptions();
        } else {
            Saber::setDefaultOptions($options); //global
            self::getDefaultClient()->setOptions($options);
        }

        return null;
    }

    public static function exceptionReport(?int $level = null): ?int
    {
        if ($level === null) {
            return self::default()['exception_report'];
        } else {
            self::default(['exception_report' => $level]);
        }

        return null;
    }

    public static function exceptionHandle(callable $handle): void
    {
        self::default(['exception_handle' => $handle]);
    }

}
