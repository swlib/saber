# Saber

[![Latest Version](https://img.shields.io/github/release/swlib/saber.svg?style=flat-square)](https://github.com/swlib/saber/releases)
[![Build Status](https://travis-ci.org/swlib/saber.svg?branch=master)](https://travis-ci.org/swlib/saber)
[![Php Version](https://img.shields.io/badge/php-%3E=7.1-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=2.1.2-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)
[![Saber License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/swlib/saber/blob/master/LICENSE)

## Intro

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

- **PHP71** or later
- Swoole 2.1.2 or later
- **Swoole 4 is the best**




## Examples

All of Saber's static methods have a corresponding method in the instance. The static method is implemented by a default client instance.

### Coroutine

Swoole implements coroutine scheduling at the bottom layer, and the business layer does not need to be aware of it. It needs to be used in event callback functions such as `onRequet`, `onReceive`, and `onConnect`, or wrapped using the go keyword (`swoole.use_shortname` is enabled by default).

```php
go(function () {
    echo SaberGM::get('http://httpbin.org/get');
})
```

### Easy Request

```php
SaberGM::get('http://httpbin.org/get');
SaberGM::post('http://httpbin.org/post');
SaberGM::put('http://httpbin.org/put');
SaberGM::patch('http://httpbin.org/patch');
SaberGM::delete('http://httpbin.org/delete');
```

### Create Instance

API proxy service applicable

```php
$saber = Saber::create([
    'base_uri' => 'http://httpbin.org',
    'headers' => [
        'User-Agent' => null,
        'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
        'DNT' => '1'
    ],
]);
echo $saber->get('/get');
echo $saber->post('/post');
echo $saber->patch('/patch');
echo $saber->put('/put');
echo $saber->delete('/delete');
```

### Create Session

Session instance will save cookies automatically, Its implementation is browser-level complete.

```php
$session = Saber::session([
    'base_uri' => 'http://httpbin.org',
    'redirect' => 0
]);
$session->get('/cookies/set?foo=bar&k=v&apple=banana');
$session->get('/cookies/delete?k');
echo $session->get('/cookies')->body;
```

### Multi Request

Note: A concurrent redirection optimization scheme is used here. Multiple redirects are always concurrent and do not degenerate into a single request for the queue.
```php
$responses = SaberGM::requests([
    ['uri' => 'http://github.com/'],
    ['uri' => 'http://github.com/'],
    ['uri' => 'https://github.com/']
]);
echo "multi-requests [ {$responses->success_num} ok, {$responses->error_num} error ]:\n" ."consuming-time: {$responses->time}s\n";

// multi-requests [ 3 ok, 0 error ]:
// consuming-time: 0.79090881347656s
```
```php
// Arguments alias make it easier.
$saber = Saber::create(['base_uri' => 'http://httpbin.org']);
echo $saber->requests([
    ['get','/get'],
    ['post','/post'],
    ['patch','/patch'],
    ['put','/put'],
    ['delete','/delete']
]);
```

### HTTP Proxy

Support HTTP and Socks5

```php
$uri = 'http://myip.ipip.net/';
echo SaberGM::get($uri, ['proxy' => 'http://127.0.0.1:1087'])->body;
echo SaberGM::get($uri, ['proxy' => 'socks5://127.0.0.1:1086'])->body;
```

### PSR Style

```php
$bufferStream = new BufferStream();
$bufferStream->write(json_encode(['foo' => 'bar']));
$response = SaberGM::psr()
    ->withMethod('POST')
    ->withUri(new Uri('http://httpbin.org/post?foo=bar'))
    ->withQueryParams(['foo' => 'option is higher-level than uri'])
    ->withHeader('content-type', ContentType::JSON)
    ->withBody($bufferStream)
    ->exec()->recv();
echo $response->getBody();
```



## Install

**The recommended way to install Saber is through [Composer](http://getcomposer.org/)**

```shell
composer require swlib/saber:dev-master
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



## Configuration parameter table

`|` splitting multiple selectable values

| key                   | type                  | introduction                   | example                                                      | remark                                                       |
| --------------------- | --------------------- | ------------------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| protocol_version      | string                |                                | 1.1                                                          | HTTP2 in the roadmap                                         |
| base_uri              | string                |                                | `http://httpbin.org`                                         | Will merge with uri according to rfc3986                     |
| uri                   | string                |                                | `http://httpbin.org/get` \| `/get` \| `get`                  | U can use absolute and relative paths                        |
| method                | string                |                                | `get` \| `post` \| `head` \| `patch` \| `put` \| `delete`    | The underlying layer is automatically converted to uppercase |
| headers               | array                 |                                | `['DNT' => '1']` \| `['accept' => ['text/html'], ['application/xml']]` | The field names are case-insensitive, but the original case rules at the time of setting are retained. Each underlying field value is automatically split into arrays according to PSR-7. |
| cookies               | `array`\|`string`     |                                | `['foo '=> 'bar']` \| `'foo=bar; foz=baz'`                   | The underlying is automatically converted to a Cookies object and its domain is set to the current uri, with browser-level complete properties. |
| useragent             | string                |                                |                                                              | The default is macos platform chrome                         |
| redirect              | int                   | max-value                      | 5                                                            | The default is 3, 0 is not redirected.                       |
| keep_alive            | bool                  |                                | `true` \| `false`                                            | The default is true, the connection will be reused automatically when redirecting |
| content_type          | string                |                                | `text/plain` \| `Swlib\Http\ContentType::JSON`               | default is `application/x-www-form-urlencoded`               |
| data                  | `array` \| `string`   |                                | `'foo=bar&dog=cat'` \|` ['foo' => 'bar']`                    | Will automatically encode data based on content_type         |
| before                | `callable` \| `array` | interceptor before request     | `function(Request $request){}`                               | Specific reference to the interceptor section                |
| after                 | `callable` \| `array` | interceptor after response     | `function(Response $response){}`                             | Ditto.                                                       |
| timeout               | float                 |                                | 0.5                                                          | Default 5s, support millisecond timeout                      |
| proxy                 | string                |                                | `http://127.0.0.1:1087` \| `socks5://127.0.0.1:1087`         | suport `http` and `socks5`                                   |
| ssl                   | int                   | enable ssl?                    | `0=disable` `1=enable` `2=auto`                              | auto default                                                 |
| cafile                | string                | ca file                        | `__DIR__ . '/cacert.pem'`                                    |                                                              |
| ssl_verify_peer       | bool                  | Verify server certificate      | `false` \| `true`                                            | close default                                                |
| ssl_allow_self_signed | bool                  | Allow self-signed certificates | `true` \| `false`                                            | allow default                                                |


### Alias

| key          | alias         |
| ------------ | ------------- |
| method       | 0             |
| uri          | `1` \| `url`  |
| data         | `2` \| `body` |
| base_uri     | base_url      |
| after        | callback      |
| content_type | content-type  |
| cookies      | cookie        |
| headers      | header        |
| redirect     | follow        |
| form_data    | query         |
| useragent    | ua            |

