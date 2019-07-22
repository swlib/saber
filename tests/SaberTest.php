<?php
/**
 * Copyright: Toast Studio
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/14 下午10:50
 */

namespace Swlib\Tests\Saber;

use PHPUnit\Framework\TestCase;
use Swlib\Http\ContentType;
use Swlib\Http\Exception\ClientException;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Http\Exception\ServerException;
use Swlib\Http\Exception\TooManyRedirectsException;
use Swlib\Http\SwUploadFile;
use Swlib\Saber;
use Swlib\SaberGM;

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
        $responses = SaberGM::requests([
            ['get', 'https://eu.httpbin.org/get'],
            ['delete', 'https://eu.httpbin.org/delete'],
            ['post', 'https://eu.httpbin.org/post', ['foo' => 'bar']],
            ['patch', 'https://eu.httpbin.org/patch', ['foo' => 'bar']],
            ['put', 'https://eu.httpbin.org/put', ['foo' => 'bar']],
        ]);
        $this->assertEquals(0, $responses->error_num);
    }

    public function testInstanceAndRequests()
    {
        $saber = Saber::create(['base_uri' => 'https://eu.httpbin.org']);
        $responses = $saber->requests([
            ['get', '/get'],
            ['delete', '/delete'],
            ['post', '/post', ['foo' => 'bar']],
            ['patch', '/patch', ['foo' => 'bar']],
            ['put', '/put', ['foo' => 'bar']],
        ]);
        $this->assertEquals(0, $responses->error_num);
    }

    public function testDisableUA()
    {
        $this->assertEquals(
            SaberGM::default()['useragent'],
            SaberGM::get('https://eu.httpbin.org/get')->getParsedJsonArray()['headers']['User-Agent']
        );
        $response = SaberGM::get('https://eu.httpbin.org/get', ['user-agent' => null]);
        $this->assertEquals(null, $response->getParsedJsonArray()['headers']['User-Agent'] ?? null);
    }

    public function testDataParser()
    {
        [$json, $xml, $html] = SaberGM::list([
            'uri' => [
                'https://eu.httpbin.org/get',
                'https://www.javatpoint.com/xmlpages/books.xml',
                'https://eu.httpbin.org/html'
            ]
        ]);
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
        $session = Saber::session([
            'base_uri' => 'https://eu.httpbin.org',
            'redirect' => 0,
            'exception_report' => HttpExceptionMask::E_ALL ^ HttpExceptionMask::E_REDIRECT
        ]);
        $session->get('/cookies/set?apple=orange', [
            'uri_query' => ['apple' => 'banana', 'foo' => 'bar', 'k' => 'v']
        ]);
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
        $saber->get('https://eu.httpbin.org/status/401');
        $this->expectException(ServerException::class);
        $saber->get('https://eu.httpbin.org/status/500');
        $this->expectException(TooManyRedirectsException::class);
        $saber->get('https://eu.httpbin.org//redirect/1', ['redirect' => 0]);
    }

    /**
     * @depends testExceptions
     */
    public function testExceptionHandle()
    {
        $saber = Saber::create(['exception_report' => true]);
        $saber->exceptionHandle(function (\Exception $e) use (&$exception) {
            $exception = get_class($e);
            return true;
        });
        $saber->get('https://eu.httpbin.org/status/500');
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

        $res = SaberGM::post('https://eu.httpbin.org/post', null, [
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
        $responses = SaberGM::requests([
            ['uri' => 'https://www.qq.com/', 'mark' => $mark],
            ['uri' => 'https://www.qq.com']
        ]);
        $this->assertEquals($mark, $responses[0]->getSpecialMark());
    }

    public function testInterceptor()
    {
        $target = 'https://www.qq.com/';
        SaberGM::get($target, [
            'before' => function (Saber\Request $request) use (&$uri) {
                $uri = $request->getUri();
            },
            'after' => function (Saber\Response $response) use (&$success) {
                $success = $response->success;
            }
        ]);
        $this->assertEquals($target, $uri ?? '');
        $this->assertTrue($success ?? false);
    }

    public function testRetryInterceptor()
    {
        $count = 0;
        $res = SaberGM::get(
            'http://127.0.0.1:65535', [
                'exception_report' => 0,
                'timeout' => 0.001,
                'retry_time' => 999,
                'retry' => function (Saber\Request $request) use (&$count) {
                    $count++;
                    return false; // shutdown
                }
            ]
        );
        $this->assertEquals(false, $res->success);
        $this->assertEquals(1, $count);
    }

    public function testRetryTime()
    {
        $log = [];
        $res = SaberGM::get(
            'http://127.0.0.1:65535', [
                'exception_report' => 0,
                'timeout' => 0.001,
                'retry_time' => 3,
                'retry' => function (Saber\Request $request) use (&$log) {
                    $log[] = "retry {$request->getRetriedTime()}";
                }
            ]
        );
        $this->assertEquals(false, $res->success);
        $this->assertEquals(['retry 1', 'retry 2', 'retry 3'], $log);
    }

    public function testRetryAndAuth()
    {
        $uri = 'https://eu.httpbin.org/basic-auth/foo/bar';
        $res = SaberGM::get(
            $uri, [
                'exception_report' => 0,
                'retry' => function (Saber\Request $request) {
                    $request->withBasicAuth('foo', 'bar');
                }
            ]
        );
        $this->assertEquals(true, $res->success);
    }

    public function testIconv()
    {
        $this->assertContains(
            '编码转换',
            (string)SaberGM::get('http://www.ip138.com/', ['iconv' => ['gbk', 'utf-8']])
        );
    }

    public function testDownload()
    {
        $download_dir = __DIR__ . '/saber.jpg';
        $response = SaberGM::download(
            'https://ws1.sinaimg.cn/large/006DQdzWly1fsr8jt2botj31hc0wxqfs.jpg',
            $download_dir
        );
        $this->assertTrue($response->success);
        if ($response->success) {
            unlink($download_dir);
        }
    }

    public function testBeforeRedirect()
    {
        $response = SaberGM::get(
            'https://eu.httpbin.org/redirect-to?url=https://www.qq.com/', [
                'before_redirect' => function (Saber\Request $request) {
                    $this->assertEquals('https://www.qq.com/', (string)$request->getUri());
                }
            ]
        );
        $this->assertTrue($response->success);
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
        $ip = \Swoole\Coroutine::getHostByName('httpbin.org');
        $saber = Saber::create([
            'base_uri' => "http://{$ip}",
            'headers' => [
                'Host' => 'httpbin.org'
            ]
        ]);
        $this->assertTrue($saber->get('/get')->getParsedJsonArray()['headers']['Host'] === 'httpbin.org');
    }

    public function testFinalClear()
    {
        if (!SABER_SW_LE_V401) {
            $status = saber_pool_get_status();
            array_walk($status, function ($pool) {
                $this->assertEquals($pool['created'], $pool['in_pool']);
            });
        }
        $this->assertTrue(saber_pool_release());
    }

}
