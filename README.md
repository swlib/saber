# Saber

[![Latest Version](https://img.shields.io/github/release/swlib/saber.svg?style=flat-square)](https://github.com/swlib/saber/releases)
[![Build Status](https://travis-ci.org/swlib/saber.svg?branch=master)](https://travis-ci.org/swlib/saber)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=2.1.2-brightgreen.svg?maxAge=2592000)](https://github.com/swoole/swoole-src)
[![Saber License](https://img.shields.io/hexpm/l/plug.svg?maxAge=2592000)](https://github.com/swlib/saber/blob/master/LICENSE)

## 简介

HTTP军刀(呆毛王), `Swoole人性化组件库`之PHP高性能HTTP客户端, 基于Swoole原生协程, 支持多种风格操作, 底层提供高性能解决方案, 让开发者专注于功能开发, 从传统同步阻塞且配置繁琐的Curl中解放.

>  **[English Document](./README-en.md)**

- 基于Swoole协程Client开发
- 人性化使用风格, ajax.js/axios.js/requests.py用户福音, 同时支持PSR风格操作
- 浏览器级别完备的Cookie管理机制, 完美适配爬虫/API代理应用
- 请求/响应/异常拦截器
- 多请求并发, 并发重定向优化, 自动化复用长连接
- 响应报文自动编码转换
- HTTPS连接, CA证书自动化支持
- HTTP/Socks5 Proxy支持
- 重定向控制, 自动化长连接复用
- 自动化 编码请求/解析响应 数据
- 毫秒超时定时器
- 超大文件上传, 断点重传
- WebSocket连接
- 随机UA生成器

------
<br>

## 安装

最好的安装方法是通过 [Composer](http://getcomposer.org/) 包管理器 :

```shell
composer require swlib/saber:dev-master
```

------

## 依赖

- PHP7 or later
- Swoole **2.1.2** or later

------
<br>

## 协程调度

Swoole底层实现协程调度, **业务层无需感知**, 开发者可以无感知的**用同步的代码编写方式达到异步IO的效果和超高性能**，避免了传统异步回调所带来的离散的代码逻辑和陷入多层回调中导致代码无法维护.

需要在`onRequet`, `onReceive`, `onConnect`等事件回调函数中使用, 或是使用go关键字包裹 (`swoole.use_shortname`默认开启).

```php
go(function () {
    echo SaberGM::get('http://httpbin.org/get');
})
```

------

## 目录
  - <a href="#例子">例子</a>
    - <a href="#静态方法">静态方法</a>
    - <a href="#生成实例">生成实例</a>
    - <a href="#生成会话">生成会话</a>
    - <a href="#并发请求">并发请求</a>
    - <a href="#数据解析">数据解析</a>
    - <a href="#网络代理">网络代理</a>
    - <a href="#文件上传">文件上传</a>
    - <a href="#psr风格">PSR风格</a>
    - <a href="#websocket">WebSocket</a>
    - <a href="#压力测试">压力测试</a>
    - <a href="#列式请求集">列式请求集</a>
    - <a href="#单次并发控制">单次并发控制</a>
  - <a href="#配置参数表">配置参数表</a>
    - <a href="#配置参数别名">配置参数别名</a>
  - <a href="#拦截器">拦截器</a>
  - <a href="#cookies">Cookies</a>
      - <a href="#属性">属性</a>
      - <a href="#任意格式互转">任意格式互转</a>
      - <a href="#域名路径和过期时限校验">域名路径和过期时限校验</a>
      - <a href="#持久化存储">持久化存储</a>
  - <a href="#异常机制">异常机制</a>
      - <a href="#捕获例子">捕获例子</a>
    - <a href="#异常报告级别控制">异常报告级别控制</a>
      - <a href="#掩码表">掩码表</a>
    - <a href="#异常自定义处理函数">异常自定义处理函数</a>
  - <a href="#road-map">Road Map</a>
      - <a href="#why-not-http2-?">Why not Http2 ?</a>
  - <a href="#ide-helper">IDE Helper</a>
  - <a href="#重中之重">重中之重</a>
  - <a href="#附录">附录</a>
    - <a href="#saber-api">Saber API</a>
      - <a href="#swlibsabergm">Swlib\SaberGM</a>
      - <a href="#swlibsaber">Swlib\Saber</a>
      - <a href="#swlibsaberrequest">Swlib\Saber\Request</a>
      - <a href="#swlibsaberresponse">Swlib\Saber\Response</a>
      - <a href="#swlibsaberrequestqueue">Swlib\Saber\RequestQueue</a>
      - <a href="#swlibsaberresponsemap">Swlib\Saber\ResponseMap</a>


------

## 例子

### 静态方法

> 数据自动打包: 传入的data会自动转换成content-type所指定的类型格式
>
> 默认为`x-www-form-urlencoded`, 也支持`json`等其它格式

`SaberGM ` := `Saber Global Manager`

```php
SaberGM::get('http://httpbin.org/get');
SaberGM::delete('http://httpbin.org/delete');
SaberGM::post('http://httpbin.org/post', ['foo' => 'bar']);
SaberGM::put('http://httpbin.org/put', ['foo' => 'bar']);
SaberGM::patch('http://httpbin.org/patch', ['foo' => 'bar']);
```

### 生成实例

适用API代理服务

```php
$saber = Saber::create([
    'base_uri' => 'http://httpbin.org',
    'headers' => [
        'Accept-Language' => 'en,zh-CN;q=0.9,zh;q=0.8',
        'Content-Type' => ContentType::JSON,
        'DNT' => '1',
        'User-Agent' => null
    ]
]);
echo $saber->get('/get');
echo $saber->delete('/delete');
echo $saber->post('/post', ['foo' => 'bar']);
echo $saber->patch('/patch', ['foo' => 'bar']);
echo $saber->put('/put', ['foo' => 'bar']);
```

### 生成会话

Session会自动保存cookie信息, 其实现是[**浏览器级别完备**](#cookies)的

```php
$session = Saber::session([
    'base_uri' => 'http://httpbin.org',
    'redirect' => 0
]);
$session->get('/cookies/set?foo=bar&k=v&apple=banana');
$session->get('/cookies/delete?k');
echo $session->get('/cookies')->body;
```

### 并发请求

注意: 此处使用了并发重定向优化方案, 多个重定向总是依旧并发的而不会退化为队列的单个请求
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

### 数据解析

目前支持`json`,`xml`,`html`,`url-query`四种格式的数据快速解析

```php
[$json, $xml, $html] = SaberGM::list([
    'uri' => [
        'http://httpbin.org/get',
        'http://www.w3school.com.cn/example/xmle/note.xml',
        'http://httpbin.org/html'
    ]
]);
var_dump($json->getParsedJson());
var_dump($json->getParsedJsonObject());
var_dump($xml->getParsedXml());
var_dump($html->getParsedHtml()->getElementsByTagName('h1')->item(0)->textContent);
```

### 网络代理

支持HTTP和SOCKS5代理

```php
$uri = 'http://myip.ipip.net/';
echo SaberGM::get($uri, ['proxy' => 'http://127.0.0.1:1087'])->body;
echo SaberGM::get($uri, ['proxy' => 'socks5://127.0.0.1:1086'])->body;
```

### 文件上传

底层自动协程调度, 可支持异步发送超大文件, 断点续传

>同时上传三个文件(三种参数风格`string`| `array` |`object`)

```php
$file1 = __DIR__ . '/black.png';
$file2 = [
    'path' => __DIR__ . '/black.png',
    'name' => 'white.png',
    'type' => ContentType::$Map['png'],
    'offset' => null, //re-upload from break
    'size' => null //upload a part of the file
];
$file3 = new SwUploadFile(
    __DIR__ . '/black.png',
    'white.png',
    ContentType::$Map['png']
);

echo SaberGM::post('http://httpbin.org/post', null, [
        'files' => [
            'image1' => $file1,
            'image2' => $file2,
            'image3' => $file3
        ]
    ]
);
```

### PSR风格

```php
$response = SaberGM::psr()
    ->withMethod('POST')
    ->withUri(new Uri('http://httpbin.org/post?foo=bar'))
    ->withQueryParams(['foo' => 'option is higher-level than uri'])
    ->withHeader('content-type', ContentType::JSON)
    ->withBody(new BufferStream(json_encode(['foo' => 'bar'])))
    ->exec()->recv();

echo $response->getBody();
```

### WebSocket

> 可以通过websocketFrame数据帧的__toString方法直接打印返回数据字符串

```php
$websocket = SaberGM::websocket('ws://127.0.0.1:9999');
while (true) {
    echo $websocket->recv(1) . "\n";
    $websocket->push("hello");
    co::sleep(1);
}
```

### 压力测试

> 测试机器为最低配MacBookPro, 请求服务器为本地echo服务器

0.9秒完成6666个请求, 成功率100%.

```php
co::set(['max_coroutine' => 8191]);
go(function () {
    $requests = [];
    for ($i = 6666; $i--;) {
        $requests[] = ['uri' => 'http://127.0.0.1'];
    }
    $res = SaberGM::requests($requests);
    echo "use {$res->time}s\n";
    echo "success: $res->success_num, error: $res->error_num";
});
// on MacOS
// use 0.91531705856323s
// success: 6666, error: 0
```

### 列式请求集

在实际项目中, 经常会存在使用URL列表来配置请求的情况, 因此提供了list方法来方便使用:

```php
echo SaberGM::list([
    'uri' => [
        'http://www.qq.com/',
        'https://www.baidu.com/',
        'https://www.swoole.com/',
        'http://httpbin.org/'
    ]
]);
```

### 单次并发控制

在实际爬虫项目中, 我们往往要限制单次并发请求数量以防被服务器防火墙屏蔽, 而一个`max_co`参数就可以轻松地解决这个问题, `max_co`会将请求根据上限量分批将请求压入队列并执行收包.

```php
// max_co is the max number of concurrency request once, it's very useful to prevent server-waf limit.
$requests = array_fill(0, 10, ['uri' => 'http://www.qq.com/']);
echo SaberGM::requests($requests, ['max_co' => 5])->time."\n";
echo SaberGM::requests($requests, ['max_co' => 1])->time."\n";
```

<br>

------

## 配置参数表

`|`符号分割多种可选值

| key                   | type                  | introduction       | example                                                      | remark                                                       |
| --------------------- | --------------------- | ------------------ | ------------------------------------------------------------ | ------------------------------------------------------------ |
| protocol_version      | string                | HTTP协议版本       | 1.1                                                          | HTTP2还在规划中                                              |
| base_uri              | string                | 基础路径           | `http://httpbin.org`                                         | 将会与uri按照rfc3986合并                                     |
| uri                   | string                | 资源标识符         | `http://httpbin.org/get` \| `/get` \| `get`                  | 可以使用绝对路径和相对路径                                   |
| method                | string                | 请求方法           | `get` \| `post` \| `head` \| `patch` \| `put` \| `delete`    | 底层自动转换为大写                                           |
| headers               | array                 | 请求报头           | `['DNT' => '1']` \| `['accept' => ['text/html'], ['application/xml']]` | 字段名不区分大小写, 但会保留设定时的原始大小写规则, 底层每个字段值会根据PSR-7自动分割为数组 |
| cookies               | `array`\|`string`     |                    | `['foo '=> 'bar']` \| `'foo=bar; foz=baz'`                   | 底层自动转化为Cookies对象, 并设置其domain为当前的uri, 具有[浏览器级别的完备属性](#cookies). |
| useragent             | string                | 用户代理           |                                                              | 默认为macos平台的chrome                                      |
| redirect              | int                   | 最大重定向次数     | 5                                                            | 默认为3, 为0时不重定向.                                      |
| keep_alive            | bool                  | 是否保持连接       | `true` \| `false`                                            | 默认为true, 重定向时会自动复用连接                           |
| content_type          | string                | 发送的内容编码类型 | `text/plain` \| `Swlib\Http\ContentType::JSON`               | 默认为application/x-www-form-urlencoded                      |
| data                  | `array` \| `string`   | 发送的数据         | `'foo=bar&dog=cat'` \|` ['foo' => 'bar']`                    | 会根据content_type自动编码数据                               |
| before                | `callable` \| `array` | 请求前拦截器       | `function(Request $request){}`                               | [具体参考拦截器一节](#拦截器)                                |
| after                 | `callable` \| `array` | 响应后拦截器       | `function(Response $response){}`                             | [具体参考拦截器一节](#拦截器)                                |
| timeout               | float                 | 超时时间           | 0.5                                                          | 默认5s, 支持毫秒级超时                                       |
| proxy                 | string                | 代理               | `http://127.0.0.1:1087` \| `socks5://127.0.0.1:1087`         | 支持http和socks5                                             |
| ssl                   | int                   | 是否开启ssl连接    | `0=关闭` `1=开启` `2=自动`                                   | 默认自动                                                     |
| cafile                | string                | ca文件             | `__DIR__ . '/cacert.pem'`                                    | 默认自带                                                     |
| ssl_verify_peer       | bool                  | 验证服务器端证书   | `false` \| `true`                                            | 默认关闭                                                     |
| ssl_allow_self_signed | bool                  | 允许自签名证书     | `true` \| `false`                                            | 默认允许                                                     |
| exception_report      | int                   | 异常报告级别       | HttpExceptionMask::E_ALL                                     | 默认汇报所有异常                                             |
| exception_handle      | callable\|array       | 异常自定义处理函数 | `function(Exception $e){}`                                   | 函数返回true时可忽略错误                                     |

### 配置参数别名

为了使用方便与容错, 配置项的键值具有别名机制, 建议尽量使用本名: 

| key      | alias       |
| -------- | ----------- |
|method|0|
|uri|`1` \| `url`|
|data|`2` \| `body`|
|base_uri|base_url|
|after|callback|
|content_type|`content-type` \| `contentType`|
|cookies|cookie|
|headers|header|
|redirect|follow|
|useragent|ua|
|exception_report|`error_report` \| `report`|
|before_retry|retry|


<br>

------

## 拦截器

拦截器是Saber的一个**非常强大的特性**, 它可以让你非常方便地处理各种事情, 比如打印dev日志:

```php
SaberGM::get('http://twosee.cn/', [
    'before' => function (Saber\Request $request) {
        $uri = $request->getUri();
        echo "log: request $uri now...\n";
    },
    'after' => function (Saber\Response $response) {
        if ($response->success) {
            echo "log: success!\n";
        } else {
            echo "log: failed\n";
        }
        echo "use {$response->time}s";
    }
]);
// log: request http://twosee.cn/ now...
// log: success!
// use 0.52036285400391s
```

甚至连`异常自定义处理函数`,`会话`都是通过拦截器来实现的.

拦截器可以有多个, 会依照注册顺序执行, 并且你可以**为拦截器命名**, 只需要使用数组包裹并指定key值, 如果你要删除这个拦截器, 给它覆盖一个null值即可.

```php
[
    'after' => [
        'interceptor_new' => function(){},
        'interceptor_old' => null
    ]
]
```

<br>

------

## Cookies

Cookie的实现是**浏览器级别完备**的, 它具体参考了Chrome浏览器的实现, 并遵循其相关规则.

#### 属性

Cookies是一堆Cookie的集合, 而每个Cookie具有以下属性:

 `name`, `value`, `expires`, `path`, `session`, `secure`, `httponly`, `hostonly`

#### 任意格式互转

并且Cookies类支持多种格式互转, 如

- `foo=bar; foz=baz; apple=banana`

- `Set-Cookie: logged_in=no; domain=.github.com; path=/; expires=Tue, 06 Apr 2038 00:00:00 -0000; secure; HttpOnly`

- `['foo'=>'bar', 'foz'=>'baz']`

等格式转到Cookie类, 或是Cookie类到该几种格式的序列化.

#### 域名路径和过期时限校验

Cookie也支持域名和时限校验, 不会丢失任何信息, 如domain是`github.com`cookie, 不会出现在`help.github.com`, 除非domain不是hostonly的(`.github.com`通配).

如果是session-cookie(没有过期时间,浏览器关闭则过期的), expires属性会设置为当前时间, 你可以通过**拦截器**来对其设置具体的时间.

#### 持久化存储

通过读取Cookies的raw属性, 可以轻松地将其**持久化到数据库中**, 非常适合登录类爬虫应用.

> 更多详情具体请参考[Swlib/Http](https://github.com/swlib/http/)库文档和例子.

<br>

------

## 异常机制

Saber遵循将**业务与错误**分离的守则, 当请求任意环节失败时, **默认都将会抛出异常**.

强大的是, Saber的异常处理也是多样化的, 且和PHP的原生的异常处理一样完善.

异常的命名空间位于`Swlib\Http\Exception`

| Exception                 | Intro              | scene                                                        |
| ------------------------- | ------------------ | ------------------------------------------------------------ |
| RequestException          | 请求失败           | 请求配置错误                                                 |
| ConnectException          | 连接失败           | 如无网络连接, DNS查询失败, 超时等,  errno的值等于Linux errno。可使用socket_strerror将错误码转为错误信息。 |
| TooManyRedirectsException | 重定向次数超限     | 重定向的次数超过了设定的限制, 抛出的异常将会打印重定向追踪信息 |
| ClientException           | 客户端异常         | 服务器返回了4xx错误码                                        |
| ServerException           | 服务器异常         | 服务器返回了5xx错误码                                        |
| BadResponseException      | 未知的获取响应失败 | 服务器无响应或返回了无法识别的错误码                         |

除一般异常方法外, 所有HTTP异常类还拥有以下方法 :

| Method                 | Intro                  |
| ---------------------- | ---------------------- |
| getRequest             | 获取请求实例           |
| hasResponse            | 是否获得响应           |
| getResponse            | 获取响应实例           |
| getResponseBodySummary | 获取响应主体的摘要内容 |

#### 捕获例子

```php
try {
    echo SaberGM::get('http://httpbin.org/redirect/10');
} catch (TooManyRedirectsException $e) {
    var_dump($e->getCode());
    var_dump($e->getMessage());
    var_dump($e->hasResponse());
    echo $e->getRedirectsTrace();
}
// int(302)
// string(28) "Too many redirects occurred!"
// bool(true)
#0 http://httpbin.org/redirect/10
#1 http://httpbin.org/relative-redirect/9
#2 http://httpbin.org/relative-redirect/8
```

### 异常报告级别控制

同时, Saber亦支持以温和的方式来对待异常, 以免使用者陷入在不稳定的网络环境下, 必须在每一步都使用try包裹代码的恐慌中:

设定errorReport级别, 它是**全局生效**的, 对**已创建的实例不会生效**.

```php
// 启用所有异常但忽略重定向次数过多异常
SaberGM::exceptionReport(
    HttpExceptionMask::E_ALL ^ HttpExceptionMask::E_REDIRECT
);
```

#### 掩码表

下面的值（数值或者符号）用于建立一个二进制位掩码，来制定要报告的错误信息。可以使用按位运算符来组合这些值或者屏蔽某些类型的错误。[标志位与掩码](http://twosee.cn/2018/04/06/mask-code/)

| Mask           | Value | Intro                |
| -------------- | ----- | -------------------- |
| E_NONE         | 0     | 忽略所有异常         |
| E_REQUEST      | 1     | 对应RequestException |
| E_CONNECT      | 2     | 对应RequestException |
| E_REDIRECT     | 4     | 对应RequestException |
| E_BAD_RESPONSE | 8     | 对应BadRException    |
| E_CLIENT       | 16    | 对应ClientException  |
| E_SERVER       | 32    | 对应ServerException  |
| E_ALL          | 63    | 所有异常             |

### 异常自定义处理函数

本函数可以用你自己定义的方式来处理HTTP请求中产生的错误, 可以更加随心所欲地定义你想要捕获/忽略的异常.

注意: 除非函数返回 **TRUE** (或其它真值)，否则异常会继续抛出而不是被自定义函数捕获.

```php
SaberGM::exceptionHandle(function (\Exception $e) {
    echo get_class($e) . " is caught!";
    return true;
});
SaberGM::get('http://httpbin.org/redirect/10');
//output: Swlib\Http\Exception\TooManyRedirectsException is caught!
```

<br>

------

## Road Map

| File Upload  ✔    | WebSocket ✔ | AutoParser✔ | AutoRetry | BigFile Download | Random UA | Http2 |
| ----------------- | ----------- | ----------- | --------- | --------- | --------- | ----- |
| 4 (High-priority) | 3           | 2           | 1         | .5        | .25        | .175   |

#### Why not Http2 ?

As the main HTTP/2 benefit is that it allows multiplexing many requests within a single connection, thus [almost] removing the limit on number of simultaneous requests - and there is no such limit when talking to your own backends. Moreover, things may even become worse when using HTTP/2 to backends, due to single TCP connection being used instead of multiple ones, so Http2 Will not be a priority. ([\#ref](https://www.zhihu.com/question/268666424/answer/347026835))

------

## IDE Helper

将本项目源文件加入到IDE的 `Include Path` 中. (使用composer安装,则可以包含整个vendor文件夹)

良好的注释书写使得Saber完美支持IDE自动提示, 只要在对象后书写箭头符号即可查看所有对象方法名称, 名称都十分通俗易懂, 大量方法都遵循PSR规范或是参考[Guzzle](https://github.com/guzzle/guzzle)项目而实现.

对于底层Swoole相关类的IDE提示则需要引入[swoole-ide-helper](https://github.com/eaglewu/swoole-ide-helper)(composer在dev环境下会默认安装), 该项目会由我持续维护并推送最新代码到eaglewu持有的主仓库中.

<br>

------

## 重中之重

**欢迎提交issue和PR.**

<br>

------

## 附录

### Saber API

> 由于无法在魔术方法中使用协程(\_\_call, \_\_callStatic), 源码中的方法都是手动定义.

为了使用方便，已为所有支持的请求方法提供了别名。

#### Swlib\SaberGM
```php
public static function psr(array $options = []): Swlib\Saber\Request
public static function wait(): Swlib\Saber
public static function request(array $options = [])
public static function get(string $uri, array $options = [])
public static function delete(string $uri, array $options = [])
public static function head(string $uri, array $options = [])
public static function options(string $uri, array $options = [])
public static function post(string $uri, $data = null, array $options = [])
public static function put(string $uri, $data = null, array $options = [])
public static function patch(string $uri, $data = null, array $options = [])
public static function requests(array $requests, array $default_options = []): Swlib\Saber\ResponseMap
public static function list(array $options, array $default_options = []): Swlib\Saber\ResponseMap
public static function websocket(string $uri)
public static function default(?array $options = null): array
public static function exceptionReport(?int $level = null): int
public static function exceptionHandle(callable $handle): void
```
#### Swlib\Saber
```php
public static function create(array $options = []): self
public static function session(array $options = []): self
public function request(array $options)
public function get(string $uri, array $options = [])
public function delete(string $uri, array $options = [])
public function head(string $uri, array $options = [])
public function options(string $uri, array $options = [])
public function post(string $uri, $data = null, array $options = [])
public function put(string $uri, $data = null, array $options = [])
public function patch(string $uri, $data = null, array $options = [])
public function requests(array $requests, array $default_options = []): ResponseMap
public function list(array $options, array $default_options = []): ResponseMap
public function websocket(string $uri): Swlib\WebSocket
public function psr(array $options = []): Request
public function wait(): self
public function exceptionReport(?int $level = null): int
public function exceptionHandle(callable $handle): void
public static function getAliasMap(): array
public function setOptions(array $options = [], ?Swlib\Saber\Request $request = null): self
public static function getDefaultOptions(): array
public static function setDefaultOptions(array $options = [])
```
#### Swlib\Saber\Request
```php
public function getExceptionReport(): int
public function setExceptionReport(int $level): self
public function isWaiting(): bool
public function getSSL(): int
public function withSSL(int $mode = 2): self
public function getCAFile(): string
public function withCAFile(string $ca_file = '/Users/twosee/Toast/swlib/saber/src/cacert.pem'): self
public function withSSLVerifyPeer(bool $verify_peer = false, ?string $ssl_host_name = ''): self
public function withSSLAllowSelfSigned(bool $allow = true): self
public function getSSLConf()
public function getKeepAlive()
public function withKeepAlive(bool $enable): self
public function withBasicAuth(?string $username = null, ?string $password = null): self
public function withXHR(bool $enable = true)
public function getProxy(): array
public function withProxy(string $host, int $port): self
public function withSocks5(string $host, int $port, ?string $username, ?string $password): self
public function withoutProxy(): self
public function getTimeout(): float
public function withTimeout(float $timeout): self
public function getRedirect(): int
public function getName()
public function withName($name): self
public function withRedirect(int $time): self
public function isInQueue(): bool
public function withInQueue(bool $enable): self
public function getRetryTime(): int
public function withRetryTime(int $time): self
public function withAutoIconv(bool $enable): self
public function withExpectCharset(string $source = 'auto', string $target = 'utf-8', bool $use_mb = false): self
public function resetClient($client)
public function exec()
public function recv()
public function getRequestTarget(): string
public function withRequestTarget($requestTarget): self
public function getMethod(): string
public function withMethod($method): self
public function getUri(): Psr\Http\Message\UriInterface
public function withUri(?Psr\Http\Message\UriInterface $uri, $preserveHost = false): self
public function getCookieParams(): array
public function getCookieParam(string $name): string
public function withCookieParam(string $name, ?string $value): self
public function withCookieParams(array $cookies): self
public function getQueryParam(string $name): string
public function getQueryParams(): array
public function withQueryParam(string $name, ?string $value): self
public function withQueryParams(array $query): self
public function getParsedBody(?string $name = null)
public function withParsedBody($data): self
public function getUploadedFile(string $name): Psr\Http\Message\UploadedFileInterface
public function getUploadedFiles(): array
public function withUploadedFile(string $name, ?Psr\Http\Message\UploadedFileInterface $uploadedFile): self
public function withoutUploadedFile(string $name): self
public function withUploadedFiles(array $uploadedFiles): self
public function __toString()
public function getProtocolVersion(): string
public function withProtocolVersion($version): self
public function hasHeader($name): bool
public function getHeader($name): array
public function getHeaderLine($name): string
public function getHeaders(bool $implode = false, bool $ucwords = false): array
public function getHeadersString(bool $ucwords = true): string
public function withHeader($raw_name, $value): self
public function withHeaders(array $headers): self
public function withAddedHeaders(array $headers): self
public function withAddedHeader($raw_name, $value): self
public function withoutHeader($name): self
public function getBody(): Psr\Http\Message\StreamInterface
public function withBody(?Psr\Http\Message\StreamInterface $body): self
public function getCookies()
public function setCookie(array $options): self
public function unsetCookie(string $name, string $path = '', string $domain = ''): self
public function withInterceptor(string $name, array $interceptor)
public function withAddedInterceptor(string $name, array $functions): self
public function removeInterceptor(string $name): self
public function callInterceptor(string $name, $arguments)
public function getSpecialMark(string $name = 'default')
public function withSpecialMark($mark, string $name = 'default'): self
```
#### Swlib\Saber\Response
```php
public function getUri(): Psr\Http\Message\UriInterface
public function isSuccess(): bool
public function getStatusCode()
public function withStatus($code, $reasonPhrase = '')
public function getReasonPhrase()
public function __toString()
public function getProtocolVersion(): string
public function withProtocolVersion($version): self
public function hasHeader($name): bool
public function getHeader($name): array
public function getHeaderLine($name): string
public function getHeaders(bool $implode = false, bool $ucwords = false): array
public function getHeadersString(bool $ucwords = true): string
public function withHeader($raw_name, $value): self
public function withHeaders(array $headers): self
public function withAddedHeaders(array $headers): self
public function withAddedHeader($raw_name, $value): self
public function withoutHeader($name): self
public function getBody(): Psr\Http\Message\StreamInterface
public function withBody(?Psr\Http\Message\StreamInterface $body): self
public function getCookies()
public function setCookie(array $options): self
public function unsetCookie(string $name, string $path = '', string $domain = ''): self
public function getSpecialMark(string $name = 'default')
public function withSpecialMark($mark, string $name = 'default'): self
public function getParsedJsonArray(bool $reParse = false): array
public function getParsedJsonObject(bool $reParse = false): object
public function getParsedQueryArray(bool $reParse = false): array
public function getParsedXmlArray(bool $reParse = false): array
public function getParsedXmlObject(bool $reParse = false): SimpleXMLElement
public function getParsedDomObject(bool $reParse = false): DOMDocument
public function getDataRegexMatch(string $regex, $group = -1, int $fill_size)
public function getDataRegexMatches(string $regex, int $flag): array
public function isExistInData(string $needle, int $offset)
```
#### Swlib\Saber\RequestQueue
```php
public function enqueue($request)
public function getMaxConcurrency(): int
public function withMaxConcurrency(int $num = -1): self
public function recv(): Swlib\Saber\ResponseMap
public function withInterceptor(string $name, array $interceptor)
public function withAddedInterceptor(string $name, array $functions): self
public function removeInterceptor(string $name): self
public function callInterceptor(string $name, $arguments)
```
#### Swlib\Saber\ResponseMap
```php
public $time = 0.0;
public $status_map = [];
public $success_map = [];
public $success_num = 0;
public $error_num = 0;
public function offsetSet($index, $response)
public function __toString()
```

