# Saber

[![Latest Version](https://img.shields.io/github/release/swlib/swlib.svg?style=flat-square)](https://github.com/swlib/saber/releases)
[![Build Status](https://travis-ci.org/swlib/saber.svg?branch=master)](https://github.com/swlib/saber/releases)
[![Php Version](https://img.shields.io/badge/php-%3E=7.1-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=2.1.2-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)
[![Saber License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/swlib/saber/blob/master/LICENSE)

## 简介

HTTP军刀, `Swoole人性化组件库`之PHP高性能HTTP客户端, 基于Swoole原生协程, 支持多种风格操作, 底层提供高性能解决方案, 让开发者专注于功能开发, 从传统同步阻塞且配置繁琐的Curl中解放.

>  **[English Document](./README-en.md)**

- 基于Swoole协程Client开发
- 人性化使用风格, ajax.js/axios.js/requests.py用户福音, 同时支持PSR风格操作
- 浏览器级别完备的Cookie管理机制, 完美适配爬虫/API代理应用
- 请求/响应拦截器
- 多请求并发, 并发重定向优化, 自动化复用长连接
- 响应报文自动编码转换
- HTTPS连接, CA证书自动化支持
- HTTP/Socks5 Proxy支持
- 重定向控制, 自动化长连接复用
- 自动化 编码请求/解析响应 数据
- 毫秒超时定时器
- 随机UA生成器




## 依赖

- PHP7 or later
- Swoole **2.1.2** or later




## 例子

Saber的所有静态方法在实例中都有对应的方法存在, 静态方法是基于一个默认的客户端实例实现的.

### 协程

Swoole底层实现协程调度, 业务层无需感知, 需要在`onRequet`, `onReceive`, `onConnect`等事件回调函数中使用, 或是使用go关键字包裹 (`swoole.use_shortname`默认开启).

```php
go(function () {
    echo Saber::get('http://httpbin.org/get');
})
```

### 简单请求

```php
Saber::get('http://httpbin.org/get');
Saber::post('http://httpbin.org/post');
Saber::put('http://httpbin.org/put');
Saber::patch('http://httpbin.org/patch');
Saber::delete('http://httpbin.org/delete');
```

### 生成实例

适用API代理服务

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

### 并发请求
注意: 此处使用了并发重定向优化方案, 多个重定向总是依旧并发的而不会退化为队列的单个请求.
```php
$responses = Saber::requests([
    ['uri' => 'http://github.com/'],
    ['uri' => 'http://github.com/'],
    ['uri' => 'https://github.com/']
]);
echo "multi-requests [ {$responses->success_num} ok, {$responses->error_num} error ]:\n" ."consuming-time: {$responses->time}s\n";

// multi-requests [ 3 ok, 0 error ]:
// consuming-time: 0.79090881347656s
```
```php
// 别名机制可以省略参数书写参数名
$saber = Saber::create(['base_uri' => 'http://httpbin.org']);
echo $saber->requests([
    ['get','/get'],
    ['post','/post'],
    ['patch','/patch'],
    ['put','/put'],
    ['delete','/delete']
]);
```

### 网络代理

支持HTTP和SOCKS5代理

```php
$uri = 'http://myip.ipip.net/';
echo Saber::get($uri, ['proxy' => 'http://127.0.0.1:1087'])->body;
echo Saber::get($uri, ['proxy' => 'socks5://127.0.0.1:1086'])->body;
```

### PSR风格

```php
$response = Saber::psr()
    ->withMethod('POST')
    ->withUri(new Uri('http://httpbin.org/post?foo=bar'))
    ->withQueryParams(['foo' => 'option is higher-level than uri'])
    ->withHeader('content-type', ContentType::JSON)
    ->withBody(new BufferStream(json_encode(['foo' => 'bar'])))
    ->exec()->recv();

echo $response->getBody();
```



## 安装

最好的安装方法是通过 [Composer](http://getcomposer.org/) 包管理器 :

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
# Global install
mv composer.phar /usr/local/bin/composer
```

**安装Saber :**

```shell
composer require swlib/saber
```

安装后,你需要在项目中引入自动加载器 :

```php
require 'vendor/autoload.php';
```

你可以通过该命令更新 :

```
composer update
```



## 配置参数表

`|`符号分割多种可选值

| key                   | type                  | introduction       | example                                                      | remark                                                       |
| --------------------- | --------------------- | ------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| protocol_version      | string                | HTTP协议版本       | 1.1                                                          | HTTP2还在规划中                                              |
| base_uri              | string                | 基础路径           | `http://httpbin.org`                                         | 将会与uri按照rfc3986合并                                     |
| uri                   | string                | 资源标识符         | `http://httpbin.org/get` \| `/get` \| `get`                  | 可以使用绝对路径和相对路径                                   |
| method                | string                | 请求方法           | `get` \| `post` \| `head` \| `patch` \| `put` \| `delete`    | 底层自动转换为大写                                           |
| headers               | array                 | 请求报头           | `['DNT' => '1']` \| `['accept' => ['text/html'], ['application/xml']]` | 字段名不区分大小写, 但会保留设定时的原始大小写规则, 底层每个字段值会根据PSR-7自动分割为数组 |
| cookies               | `array`\|`string`     |                    | `['foo '=> 'bar']` \| `'foo=bar; foz=baz'`                   | 底层自动转化为Cookies对象, 并设置其domain为当前的uri, 具有浏览器级别的完备属性. |
| useragent             | string                | 用户代理           |                                                              | 默认为macos平台的chrome                                      |
| redirect              | int                   | 最大重定向次数     | 5                                                            | 默认为3, 为0时不重定向.                                      |
| keep_alive            | bool                  | 是否保持连接       | `true` \| `false`                                            | 默认为true, 重定向时会自动复用连接                           |
| content_type          | string                | 发送的内容编码类型 | `text/plain` \| `Swlib\Http\ContentType::JSON`               | 默认为application/x-www-form-urlencoded                      |
| data                  | `array` \| `string`   | 发送的数据         | `'foo=bar&dog=cat'` \|` ['foo' => 'bar']`                    | 会根据content_type自动编码数据                               |
| before                | `callable` \| `array` | 请求前拦截器       | `function(Request $request){}`                               | 具体参考拦截器一节                                           |
| after                 | `callable` \| `array` | 响应后拦截器       | `function(Response $response){}`                             | 具体参考拦截器一节                                           |
| timeout               | float                 | 超时时间           | 0.5                                                          | 默认5s, 支持毫秒级超时                                       |
| proxy                 | string                | 代理               | `http://127.0.0.1:1087` \| `socks5://127.0.0.1:1087`         | 支持http和socks5                                             |
| ssl                   | int                   | 是否开启ssl连接    | `0=关闭` `1=开启` `2=自动`                                   | 默认自动                                                     |
| cafile                | string                | ca文件             | `__DIR__ . '/cacert.pem'`                                    | 默认自带                                                     |
| ssl_verify_peer       | bool                  | 验证服务器端证书   | `false` \| `true`                                            | 默认关闭                                                     |
| ssl_allow_self_signed | bool                  | 允许自签名证书     | `true` \| `false`                                            | 默认允许                                                     |

### 别名

为了使用方便与容错, 配置项的键值具有别名机制, 建议尽量使用本名: 

| key      | alias       |
| -------- | ----------- |
|method|0|
|uri|`1` \| `url`|
|data|`2` \| `body`|
|base_uri|base_url|
|after|callback|
|content_type|content-type|
|cookies|cookie|
|headers|header|
|redirect|follow|
|form_data|query|
|useragent|ua|
