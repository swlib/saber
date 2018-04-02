# Saber

[![Latest Version](https://img.shields.io/github/release/swlib/swlib.svg?style=flat-square)](https://github.com/swlib/saber/releases)
[![Build Status](https://travis-ci.org/swlib/saber.svg?branch=master)](https://github.com/swlib/saber/releases)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=2.1.2-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)
[![Saber License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/swlib/saber/blob/master/LICENSE)

## 简介

The PHP high-performance HTTP client for `Swoole Humanized Component Library`, based on Swoole native coroutine client, supports multiple styles of operation, provides high-performance solutions at the bottom, allows developers to focus on feature development, and emancipate from traditional synchronous blocking network libs.

- Development based on Swoole Coroutine Client
- User-friendly style, ajax.js/axios.js/requests.py users' gospel, also supports PSR style operation
- Complete browser-level cookie management mechanism, perfect for crawler/API proxy applications
- Request/response interceptors
- Multiple requests concurrent, concurrent redirection optimization, automated multiplexing long connections
- Automatic transcoding of response messages
- HTTPS connection, CA certificate automation support
- HTTP/Socks5 Proxy Support
- Redirection control, automated long connection multiplexing
- Automation Encode Request/Parse Response Data
- Milliseconds timeout timer
- Random UA Generator




## Requirement

- PHP7 or later
- Swoole **2.1.2** or later




## Examples

All of Saber's static methods have a corresponding method in the instance. The static method is implemented by a default client instance.

### Easy Request

```php
go(function () {
    Saber::get('http://httpbin.org/get');
    Saber::post('http://httpbin.org/post');
    Saber::put('http://httpbin.org/put');
    Saber::patch('http://httpbin.org/patch');
    Saber::delete('http://httpbin.org/delete');
});
```

### Create Instance

API proxy service applicable

```php
go(function () {
    $saber = Saber::create([
        'base_uri' => 'http://httpbin.org',
        'headers' => ['Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8']
    ]);
    $response = $saber->get('/get');
    echo $response;
});
```

### Multi Request
Note: A concurrent redirection optimization scheme is used here. Multiple redirects are always concurrent and do not degenerate into a single request for the queue.
```php
go(function () {
    $responses = Saber::requests([
        ['uri' => 'http://github.com/'],
        ['uri' => 'http://github.com/'],
        ['uri' => 'https://github.com/']
    ]);
    echo "multi-requests [ {$responses->success_num} ok, {$responses->error_num} error ]:\n" .
        "consuming-time: {$responses->time}s\n";
});
// multi-requests [ 3 ok, 0 error ]:
// consuming-time: 0.79090881347656s
```
### HTTP Proxy

Support HTTP and Socks5

```php
go(function () {
    $uri = 'http://myip.ipip.net/';
    echo Saber::get($uri, ['proxy' => 'http://127.0.0.1:1087'])->body;
    echo Saber::get($uri, ['proxy' => 'socks5://127.0.0.1:1086'])->body;
});
```

### PSR Style

```php
go(function () {
    $response = Saber::psr()
        ->withMethod('POST')
        ->withUri(new Uri('http://httpbin.org/post?foo=bar'))
        ->withQueryParams(['foo' => 'option is higher-level than uri'])
        ->withHeader('content-type', ContentType::JSON)
        ->withBody(new BufferStream(json_encode(['foo' => 'bar'])))
        ->exec()->recv();

    echo $response->getBody();
});
```



## Install

**The recommended way to install Saber is through [Composer](http://getcomposer.org/)**

```shell
composer require swlib/saber
```

how to install composer?
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
```
```bash
# Global install
mv composer.phar /usr/local/bin/composer
```


After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

You can then later update Saber using composer:

```
composer update
```