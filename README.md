# Saber

[![Latest Version](https://img.shields.io/github/release/swlib/swlib.svg?style=flat-square)](https://github.com/swlib/saber/releases)
[![Build Status](https://travis-ci.org/swlib/saber.svg?branch=master)](https://github.com/swlib/saber/releases)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=2.1.2-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)
[![Saber License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/swlib/saber/blob/master/LICENSE)

## 简介

HTTP军刀, `Swoole人性化组件库`之PHP高性能HTTP客户端, 基于Swoole原生协程, 支持多种风格操作, 底层提供高性能解决方案, 让开发者专注于功能开发, 从传统同步阻塞且配置繁琐的Curl中解放.

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

### 简单请求

```php
go(function () {
    Saber::get('http://httpbin.org/get');
    Saber::post('http://httpbin.org/post');
    Saber::put('http://httpbin.org/put');
    Saber::patch('http://httpbin.org/patch');
    Saber::delete('http://httpbin.org/delete');
});
```

### 生成实例

适用API代理服务

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

### 并发请求
注意: 此处使用了并发重定向优化方案, 多个重定向总是依旧并发的而不会退化为队列的单个请求.
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
### 网络代理

支持HTTP和SOCKS5代理

```php
go(function () {
    $uri = 'http://myip.ipip.net/';
    echo Saber::get($uri, ['proxy' => 'http://127.0.0.1:1087'])->body;
    echo Saber::get($uri, ['proxy' => 'socks5://127.0.0.1:1086'])->body;
});
```

### PSR风格

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