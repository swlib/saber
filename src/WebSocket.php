<?php

/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/10 下午1:32
 */

namespace Swlib\Saber;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Uri;
use Swoole\Coroutine\Http\Client;

class WebSocket extends \Swlib\Http\Request
{

    public $client;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(UriInterface $uri, bool $mock = false)
    {
        $this->uri = $uri;
        $host = $this->uri->getHost();
        $port = $this->uri->getPort();
        $ssl = $this->uri->getScheme() === 'wss';
        if (empty($host)) {
            $host = explode('/', ($uri_string = (string) $this->uri))[0] ?? '';
            if (empty($host) || !preg_match('/\.\w+$/', $host)) {
                throw new InvalidArgumentException('Host should not be empty!');
            } else {
                $uri_string = 'ws://' . rtrim($uri_string, '/');
                $this->uri = new Uri($uri_string);
                $host = $this->uri->getHost();
            }
        }

        if (empty($port)) {
            $port = $ssl ? 443 : 80;
        }

        $this->client = new Client($host, $port, $ssl);
        if ($mock) {
            $this->withMock($ssl);
        }

        parse_str($this->uri->getQuery(), $query);
        $query = $this->getQueryParams() + $query; //attribute value first
        $query = http_build_query($query);

        $path = $this->uri->getPath() ?: '/';
        $path = empty($query) ? $path : $path . '?' . $query;

        $ret = $this->client->upgrade($path);
        if (!$ret) {
            throw new ConnectException(
                $this,
                $this->client->errCode,
                'Websocket upgrade failed by [' . swoole_strerror($this->client->errCode) . '].'
            );
        }
    }

    /**
     * enable mask to mock the browser
     */
    public function withMock(bool $ssl): self
    {
        $settings = ['websocket_mask' => true];
        if ($ssl) {
            $settings['ssl_host_name'] = $this->uri->getHost();
        }
        $this->client->set($settings);

        return $this;
    }

    public function recv(float $timeout = -1)
    {
        $ret = $this->client->recv($timeout);

        return $ret ? new WebSocketFrame($ret) : $ret;
    }

    public function push(string $data, int $opcode = WEBSOCKET_OPCODE_TEXT, bool $finish = true): bool
    {
        return $this->client->push($data, $opcode, $finish);
    }

    public function close(): bool
    {
        return $this->client->close();
    }

    public function __destruct()
    {
        $this->close();
    }
}
