<?php

/**
 * Copyright: Toast Studio
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/14 下午10:50
 */

namespace Swlib\Tests\Saber;

use Exception;
use PHPUnit\Framework\TestCase;
use Swlib\Http\ContentType;
use Swlib\Http\Exception\ClientException;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Http\Exception\ServerException;
use Swlib\Http\Exception\TooManyRedirectsException;
use Swlib\Http\SwUploadFile;
use Swlib\Http\Uri;
use Swlib\Saber;
use Swlib\Saber\ClientPool;
use Swlib\SaberGM;
use Swoole\Coroutine;

class SaberTest extends TestCase
{

    public function testExceptionReport()
    {
        $this->assertEquals(HttpExceptionMask::E_NONE, SaberGM::exceptionReport());
    }

    public function testPoolAndShortUri()
    {
        $this->assertContains('tencent', (string)SaberGM::get('www.qq.com')->getBody());
        $this->assertContains('tencent', (string)SaberGM::get('www.qq.com')->getBody());
        $this->assertTrue(saber_pool_get_status('www.qq.com:80')['created'] === 1);
    }

    public function testStaticAndRequests()
    {
        $responses = SaberGM::requests(
            [
                ['get', 'http://www.httpbin.org/get'],
                ['delete', 'http://www.httpbin.org/delete'],
                ['post', 'http://www.httpbin.org/post', ['foo' => 'bar']],
                ['patch', 'http://www.httpbin.org/patch', ['foo' => 'bar']],
                ['put', 'http://www.httpbin.org/put', ['foo' => 'bar']],
            ]
        );
        $this->assertEquals(0, $responses->error_num);
    }

    public function testInstanceAndRequests()
    {
        $saber = Saber::create(['base_uri' => 'http://www.httpbin.org']);
        $responses = $saber->requests(
            [
                ['get', '/get'],
                ['delete', '/delete'],
                ['post', '/post', ['foo' => 'bar']],
                ['patch', '/patch', ['foo' => 'bar']],
                ['put', '/put', ['foo' => 'bar']],
            ]
        );
        $this->assertEquals(0, $responses->error_num);
    }

    public function testDisableUA()
    {
        $this->assertEquals(
            SaberGM::default()['useragent'],
            SaberGM::get('http://www.httpbin.org/get')->getParsedJsonArray()['headers']['User-Agent']
        );
        $response = SaberGM::get('http://www.httpbin.org/get', ['user-agent' => null]);
        $this->assertEquals(null, $response->getParsedJsonArray()['headers']['User-Agent'] ?? null);
    }

    public function testDataParser()
    {
        [$json, $xml, $html] = SaberGM::list(
            [
                'uri' => [
                    'https://www.httpbin.org/get',
                    'https://www.javatpoint.com/xmlpages/books.xml',
                    'http://www.httpbin.org/html'
                ]
            ]
        );
        $this->assertEquals((string)$json->getUri(), $json->getParsedJsonArray()['url']);
        $this->assertEquals((string)$json->getUri(), $json->getParsedJsonObject()->url);
        $this->assertEquals('Everyday Italian', $xml->getParsedXmlObject()->book[0]->title);
        $this->assertStringStartsWith(
            'Herman',
            $html->getParsedDomObject()->getElementsByTagName('h1')->item(0)->textContent
        );
    }

    public function testSessionAndUriQuery()
    {
        $session = Saber::session(
            [
                'base_uri' => 'http://www.httpbin.org',
                'exception_report' => HttpExceptionMask::E_ALL ^ HttpExceptionMask::E_REDIRECT
            ]
        );
        $session->get(
            '/cookies/set?apple=orange',
            [
                'uri_query' => ['apple' => 'banana', 'foo' => 'bar', 'k' => 'v']
            ]
        );
        $session->get('/cookies/delete?k');
        $cookies = $session->get('/cookies')->getParsedJsonArray()['cookies'];
        $expected = ['apple' => 'banana', 'foo' => 'bar'];
        self::assertEquals($expected, $cookies);
    }

    public function testExceptions()
    {
        $saber = Saber::create(['exception_report' => true]);
        $this->expectException(ConnectException::class);
        $saber->get('https://www.qq.com', ['timeout' => 0.001]);
        $this->expectException(ConnectException::class);
        $saber->get('http://foo.bar');
        $this->expectException(ClientException::class);
        $saber->get('http://www.httpbin.org/status/401');
        $this->expectException(ServerException::class);
        $saber->get('http://www.httpbin.org/status/500');
        $this->expectException(TooManyRedirectsException::class);
        $saber->get('http://httpbingo.org/redirect/3', ['redirect' => 1]);
    }

    /**
     * @depends testExceptions
     */
    public function testExceptionHandle()
    {
        $saber = Saber::create(['exception_report' => true]);
        $saber->exceptionHandle(
            function (Exception $e) use (&$exception) {
                $exception = get_class($e);
                return true;
            }
        );
        $saber->get('http://www.httpbin.org/status/500');
        $this->assertEquals(ServerException::class, $exception);
    }

    public function testUploadFiles()
    {
        $file1 = __DIR__ . '/resources/black.png';
        $this->assertFileExists($file1);
        $file2 = [
            'path' => __DIR__ . '/resources/black.png',
            'name' => 'white.png',
            'type' => ContentType::get('png'),
            'offset' => null, //re-upload from break
            'size' => null //upload a part of the file
        ];
        $file3 = new SwUploadFile(
            __DIR__ . '/resources/black.png',
            'white.png',
            ContentType::get('png')
        );

        $res = SaberGM::post(
            'http://www.httpbin.org/post',
            null,
            [
                'files' => [
                    'image1' => $file1,
                    'image2' => $file2,
                    'image3' => $file3
                ]
            ]
        );
        $files = array_keys($res->getParsedJsonArray()['files']);
        $this->assertEquals(['image1', 'image2', 'image3'], $files);
    }

    public function testMark()
    {
        $mark = 'it is request one!';
        $responses = SaberGM::requests(
            [
                ['uri' => 'https://www.qq.com/', 'mark' => $mark],
                ['uri' => 'https://www.qq.com']
            ]
        );
        $this->assertEquals($mark, $responses[0]->getSpecialMark());
    }

    public function testInterceptor()
    {
        $target = 'https://www.qq.com/';
        SaberGM::get(
            $target,
            [
                'before' => function (Saber\Request $request) use (&$uri) {
                    $uri = $request->getUri();
                },
                'after' => function (Saber\Response $response) use (&$success) {
                    $success = $response->getSuccess();
                }
            ]
        );
        $this->assertEquals($target, $uri ?? '');
        $this->assertTrue($success ?? false);
    }

    public function testList()
    {
        $uri_list = [
            'https://www.qq.com/',
            'https://www.cust.edu.cn/'
        ];
        $res = SaberGM::list(['uri' => $uri_list]);
        $this->assertEquals(count($uri_list), $res->success_num);
    }

    public function testRetryInterceptor()
    {
        $count = 0;
        $res = SaberGM::get(
            'http://127.0.0.1:65535',
            [
                'exception_report' => 0,
                'timeout' => 0.001,
                'retry_time' => 999,
                'retry' => function (Saber\Request $request) use (&$count) {
                    $count++;
                    return false; // shutdown
                }
            ]
        );
        $this->assertEquals(false, $res->getSuccess());
        $this->assertEquals(1, $count);
    }

    public function testRetryTime()
    {
        $log = [];
        $res = SaberGM::get(
            'http://127.0.0.1:65535',
            [
                'exception_report' => 0,
                'timeout' => 0.001,
                'retry_time' => 3,
                'retry' => function (Saber\Request $request) use (&$log) {
                    $log[] = "retry {$request->getRetriedTime()}";
                }
            ]
        );
        $this->assertEquals(false, $res->getSuccess());
        $this->assertEquals(['retry 1', 'retry 2', 'retry 3'], $log);
    }

    public function testRetryAndAuth()
    {
        $uri = 'http://www.httpbin.org/basic-auth/foo/bar';
        $res = SaberGM::get(
            $uri,
            [
                'exception_report' => HttpExceptionMask::E_NONE,
                'retry' => function (Saber\Request $request) {
                    $request->withBasicAuth('foo', 'bar');
                }
            ]
        );
        $this->assertEquals(true, $res->getSuccess());
    }

    public function testAuthWithUserInfoInURI()
    {
        $uri = 'http://foo:bar@www.httpbin.org/basic-auth/foo/bar';
        $res = SaberGM::get($uri);
        $this->assertEquals(true, $res->getSuccess());
    }

    public function testAuthOverrideUserInfoInURI()
    {
        $uri = 'http://doo:zar@www.httpbin.org/basic-auth/foo/bar';
        $res = SaberGM::get(
            $uri,
            [
                'before' => function (Saber\Request $request) {
                    $request->withBasicAuth('foo', 'bar');
                }
            ]
        );

        $this->assertEquals(true, $res->getSuccess());
    }

    public function testIconv()
    {
        $this->assertContains(
            '编码转换',
            (string)SaberGM::get('https://www.ip138.com/', ['iconv' => ['gbk', 'utf-8']])->getBody()
        );
    }

    public function testDownload()
    {
        $download_dir = __DIR__ . '/mascot.png';
        $response = SaberGM::download(
            'https://raw.githubusercontent.com/swoole/swoole-src/master/mascot.png',
            $download_dir
        );
        $this->assertTrue($response->getSuccess());
        if ($response->getSuccess()) {
            unlink($download_dir);
        }
    }

    public function testBeforeRedirect()
    {
        $response = SaberGM::get(
            'http://httpbingo.org/redirect-to?url=https://www.qq.com/',
            [
                'before_redirect' => function (Saber\Request $request) {
                    $this->assertEquals('https://www.qq.com/', (string)$request->getUri());
                }
            ]
        );
        $this->assertTrue($response->getSuccess());
        $this->assertContains('www.qq.com', (string)$response->body);
    }

    public function testWebSocket()
    {
        global $server_list;
        list($ip, $port) = array_values($server_list['mixed']);
        $ws = SaberGM::websocket("ws://{$ip}:{$port}");
        $this->assertEquals($ws->recv(), "server: hello, welcome\n");
        for ($i = 0; $i < 5; $i++) {
            $ws->push("hello server\n");
            $this->assertEquals($ws->recv(1), "server-reply: hello client\n");
        }
        $ws->close();
    }

    public function testWithHost()
    {
        $ip = Coroutine::getHostByName('httpbin.org');
        $saber = Saber::create(
            [
                'base_uri' => "http://{$ip}",
                'headers' => [
                    'Host' => 'httpbin.org'
                ]
            ]
        );
        $this->assertTrue($saber->get('/get')->getParsedJsonArray()['headers']['Host'] === 'httpbin.org');
    }

    public function testFinalClear()
    {
        if (!SABER_SW_LE_V401) {
            $status = saber_pool_get_status();
            array_walk(
                $status,
                function ($pool) {
                    $this->assertEquals($pool['created'], $pool['in_pool']);
                }
            );
        }
        $this->assertTrue(saber_pool_release());
    }

    public function testKeepAliveAmongSameHostAndPortWithOutUsePool()
    {
        // FIXME
        if (true) {
            return;
        }

        global $server_list;
        [$ip, $port] = array_values($server_list['httpd']);
        $saber = Saber::create(
            [
                'base_uri' => "http://$ip:$port",
                //'base_uri' => "http://127.0.0.1:8081",
                'use_pool' => false,
                'exception_report' => HttpExceptionMask::E_ALL
            ]
        );

        $ReqWithSaber = $saber->get('/anything?dump_info=$ReqWithSaber')->getParsedJsonArray();
        $ReqWithSaber2 = $saber->get('/anything?dump_info=$ReqWithSaber2')->getParsedJsonArray();
        $ReqWithSaberPSR = $saber->request(['psr' => 1])->withMethod('GET')->withUri(
            new Uri("http://$ip:$port/anything?dump_info=ReqWithSaberPSR")
        )->exec()->recv()->getParsedJsonArray();
        $ReqWithSaberPSR2 = $saber->request(['psr' => 1])->withMethod('GET')->withUri(
            new Uri("http://$ip:$port/anything?dump_info=ReqWithSaberPSR2")
        )->exec()->recv()->getParsedJsonArray();
        // $ReqAfterAnotherPort = $saber->get('http://httpbin.org/anything?dump_info=$ReqWithSaber2')->getParsedJsonArray();
        $ReqAfterAnotherPort = $saber->get('/anything?dump_info=$ReqWithSaber2')->getParsedJsonArray();

        $this->assertTrue($ReqWithSaber['server']['remote_port'] === $ReqWithSaber2['server']['remote_port']);
        $this->assertTrue($ReqWithSaberPSR['server']['remote_port'] === $ReqWithSaberPSR2['server']['remote_port']);
        $this->assertTrue($ReqWithSaber2['server']['remote_port'] === $ReqWithSaberPSR2['server']['remote_port']);
        $this->assertFalse($ReqWithSaber2['server']['remote_port'] === $ReqAfterAnotherPort['server']['remote_port']);
        $this->assertTrue($ReqWithSaber2['header']['connection'] === 'keep-alive');
        $this->assertTrue($ReqWithSaberPSR2['header']['connection'] === 'keep-alive');
    }

    public function testPostDataAndUploadFile()
    {
        $file = __DIR__ . '/resources/black.png';
        $array = SaberGM::post(
            'http://httpbin.org/post',
            ['foo' => 'bar'],
            [
                'files' => [
                    'image' => $file,
                ]
            ]
        )->getParsedJsonArray();

        $this->assertEquals($array['form']['foo'], 'bar');
    }

    public function testNonRedirect()
    {
        $saber = Saber::create(['exception_report' => true]);
        $res = $saber->get('http://baidu.com', ['redirect' => 0]);
        $this->assertTrue((string)$res->body !== '');
    }

    public function testPoolKeyConfig()
    {
        $saber = Saber::create(['exception_report' => true]);
        $saber->get('http://www.httpbin.org', ['use_pool' => 5, 'pool_key' => function (Saber\Request $request) {
            return 'test_pool';
        }]);
        $this->assertEquals(saber_pool_get_status('test_pool')['max'], 5);
    }

    public function testNoPoolKeyConfig()
    {
        $saber = Saber::create(['exception_report' => true]);
        $saber->get('http://www.httpbin.org', ['use_pool' => 5]);
        $this->assertEquals(saber_pool_get_status('www.httpbin.org:80')['max'], 5);
    }

    public function testReleasePool()
    {
        $saber = Saber::create(['exception_report' => true]);
        $pool = ClientPool::getInstance();
        $index = 0;
        for ($i = 0; $i < 5; $i++) {
            go(function () use ($saber, $pool, &$index) {
                $saber->get('http://www.httpbin.org', ['use_pool' => 5, 'pool_key' => function (Saber\Request $request) {
                    return 'test_release';
                }]);
                $index++;
                if ($index === 1) {
                    $this->assertEquals($pool->getStatus('test_release')['in_pool'], 1);
                } elseif ($index === 2) {
                    $this->assertEquals($pool->getStatus('test_release')['in_pool'], 2);
                    $pool->release('test_release');
                    $this->assertNull($pool->getStatus('test_release')['in_pool']);
                } else {
                    $this->assertNull($pool->getStatus('test_release')['in_pool']);
                }
            });
        }
    }

    public function testSSLCiphers()
    {
        $ja3 = [];
        for ($i = 0; $i < 2; $i++) {
            $saber = Saber::create(['exception_report' => true]);
            $ja3[] = $saber->get("https://ja3er.com/json")->getParsedJsonArray()['ja3_hash'];
        }
        [$j1, $j2] = $ja3;
        $this->assertEquals($j1, $j2);

        $ciphers = explode(':', 'ECDH+AESGCM:DH+AESGCM:ECDH+AES256:DH+AES256:ECDH+AES128:DH+AES:ECDH+HIGH:DH+HIGH:ECDH+3DES:DH+3DES:RSA+AESGCM:RSA+AES:RSA+HIGH:RSA+3DES');
        $ja3 = [];
        for ($i = 0; $i < 2; $i++) {
            shuffle($ciphers);
            $saber = Saber::create(['exception_report' => true]);
            $ja3[] = $saber->get("https://ja3er.com/json", ['ssl_ciphers' => implode(':', $ciphers) . ':!aNULL:!eNULL:!MD5'])->getParsedJsonArray()['ja3_hash'];
        }
        [$j1, $j2] = $ja3;
        $this->assertNotEquals($j1, $j2);

        $ja3 = [];
        $saber = Saber::create(['exception_report' => true]);
        for ($i = 0; $i < 2; $i++) {
            shuffle($ciphers);
            $ja3[] = $saber->get("https://ja3er.com/json", ['ssl_ciphers' => implode(':', $ciphers) . ':!aNULL:!eNULL:!MD5'])->getParsedJsonArray()['ja3_hash'];
        }
        [$j1, $j2] = $ja3;
        $this->assertNotEquals($j1, $j2);
    }
}
