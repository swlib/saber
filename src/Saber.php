<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/27 上午1:10
 */

namespace Swlib;

use Swlib\Saber\Client;

class Saber
{
    private static function getDefaultClient()
    {
        static $defaultClient;

        return $defaultClient ?? $defaultClient = Client::create();
    }

    public static function create(array $options = [])
    {
        return Client::create($options);
    }

    public static function psr(array $options = [])
    {
        return self::getDefaultClient()->request(['psr' => true] + $options);
    }

    /** @return \Swlib\Saber\Client */
    public static function wait(array $options = [])
    {
        return self::create($options)->wait();
    }

    public static function request(array $options = [])
    {
        return self::getDefaultClient()->request($options);
    }

    public static function requests(array $requests, array $default_options = [])
    {
        return self::getDefaultClient()->requests($requests, $default_options);
    }

    public static function get(string $uri, array $options = [])
    {
        $def = [
            'uri' => $uri,
            'method' => 'GET',
        ];

        return self::getDefaultClient()->request($def + $options);
    }

    public static function delete(string $uri, array $options = [])
    {
        $def = [
            'uri' => $uri,
            'method' => 'DELETE',
        ];

        return self::getDefaultClient()->request($def + $options);
    }

    public static function head(string $uri, array $options = [])
    {
        $def = [
            'uri' => $uri,
            'method' => 'HEAD',
        ];

        return self::getDefaultClient()->request($def + $options);
    }

    public static function post(string $uri, $data = null, array $options = [])
    {
        $def = [
            'uri' => $uri,
            'method' => 'POST',
            'data' => $data,
        ];

        return self::getDefaultClient()->request($def + $options);
    }

    public static function put(string $uri, $data = null, array $options = [])
    {
        $def = [
            'uri' => $uri,
            'method' => 'PUT',
            'data' => $data,
        ];

        return self::getDefaultClient()->request($def + $options);
    }

    public static function patch(string $uri, $data = null, array $options = [])
    {
        $def = [
            'uri' => $uri,
            'method' => 'PATCH',
            'data' => $data,
        ];

        return self::getDefaultClient()->request($def + $options);
    }

}