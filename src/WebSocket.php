<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/10 下午1:32
 */

namespace Swlib\Saber;

use Psr\Http\Message\UriInterface;
use Swlib\Http\Exception\ConnectException;

class WebSocket extends \Swlib\Http\Request
{

    public $client;

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct(UriInterface $uri)
    {
        //Todo: improve it
        $this->withUri($uri);
        $this->client = new \Swoole\Coroutine\Http\Client(
            $uri->getHost(),
            $uri->getPort(),
            $uri->getScheme() == 'wss'
        );
        $ret = $this->client->upgrade($uri->getPath() ?: '/');
        if (!$ret) {
            throw new ConnectException(
                $this, $this->client->errCode,
                'Websocket upgrade failed by [' . socket_strerror($this->client->errCode) . '].'
            );
        }
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
