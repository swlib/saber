<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/1/10 上午7:57
 */

namespace Swlib\Saber;

use Swlib\Http\BufferStream;
use Swlib\Http\CookiesManagerTrait;

class Response extends \Swlib\Http\Response
{

    public $redirect_headers = [];
    public $success = false;
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

        /** 判定响应是否成功 */
        $this->withStatus($request->client->statusCode);
        if ($request->client->statusCode >= 200 && $request->client->statusCode < 300) {
            $this->success = true;
        }
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
    }

}