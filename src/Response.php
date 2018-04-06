<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/1/10 上午7:57
 */

namespace Swlib\Saber;

use Swlib\Http\BufferStream;
use Swlib\Http\CookiesManagerTrait;
use Swlib\Http\Exception\BadResponseException;
use Swlib\Http\Exception\ClientException;
use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Http\Exception\ServerException;
use Swlib\Http\Exception\TooManyRedirectsException;

class Response extends \Swlib\Http\Response
{

    public $redirect_headers = [];
    public $success = false;
    /**
     * @var int $statusCode
     * Http status code, such as 200, 404 and so on. If the status code is negative, there is a problem with the connection.
     * -1: When the connection times out, the server is not listening on the port or the network is lost. You can read $errCode to obtain the specific network error code.
     * -2: The request timed out and the server did not return the response within the specified timeout time
     * -3: After the client sends a request, the server forcibly cuts off the connection
     */
    public $statusCode = 0;
    public $reasonPhrase = 'Failed';
    public $uri;
    public $time;
    /** @var \Swlib\Http\StreamInterface */
    public $body;

    use CookiesManagerTrait;

    /** @noinspection PhpMissingParentConstructorInspection */
    function __construct(Request $request)
    {
        /** consuming time */
        $this->time = $request->_time;
        /** status code */
        $this->withStatus($request->client->statusCode);
        /** 设定uri */
        $this->uri = $request->getUri();

        /** 初始化 */
        $this->withHeaders($request->client->headers);

        /** 记录重定向头 */
        $this->redirect_headers = $request->_redirect_headers; //记录重定向前的headers

        /** 置Cookie对象 */
        $this->cookies = $request->incremental_cookies;

        /** 转码 */
        if (!empty($body = $request->client->body)) {
            if (stristr($request->client->headers['content-type'], 'utf-8') === false) {
                $type = explode('=', $request->client->headers['content-type'])[1] ?? null;
                if ($type) {
                    $body = iconv(strtoupper($type), 'UTF-8//IGNORE', $body);
                }
            }
        } else {
            $body = '';
        }

        $this->withBody(new BufferStream($body));

        $e_level = $request->getExceptionReport();
        if ($this->statusCode >= 200 && $this->statusCode < 300) {
            $this->success = true;
        } else {
            $exception = null;
            if ($this->statusCode >= 300 && $this->statusCode < 400) {
                if ($e_level & HttpExceptionMask::E_REDIRECT) {
                    $exception = new TooManyRedirectsException($request, $this, $this->statusCode,
                        $this->redirect_headers);
                }
            } elseif ($this->statusCode >= 400 && $this->statusCode < 500) {
                if ($e_level & HttpExceptionMask::E_CLIENT) {
                    $exception = new ClientException($request, $this, $this->statusCode);
                }
            } elseif ($this->statusCode >= 500) {
                if ($e_level & HttpExceptionMask::E_SERVER) {
                    $exception = new ServerException($request, $this, $this->statusCode);
                }
            } elseif ($e_level & HttpExceptionMask::E_BAD_RESPONSE) {
                $exception = new BadResponseException($request, $this, $this->statusCode);
            }
            if ($exception) {
                $ret = $request->callInterceptor('exception', $exception);
                if (!$ret) {
                    throw $exception;
                }
            }
        }
    }

}