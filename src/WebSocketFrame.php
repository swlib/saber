<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/4/10 下午4:59
 */

namespace Swlib\Saber;

use Swoole\WebSocket\Frame;

class WebSocketFrame
{
    /** @var bool */
    public $finish = true;
    /** @var string */
    public $opcode;
    /** @var string */
    public $data;

    public function __construct(Frame $frame)
    {
        foreach ($frame as $key => $val) {
            $this->$key = $val;
        }
    }

    public function getOpcodeDefinition()
    {
        static $map = [
            1 => 'WEBSOCKET_OPCODE_TEXT',
            2 => 'WEBSOCKET_OPCODE_BINARY',
            9 => 'WEBSOCKET_OPCODE_PING'
        ];

        return $map[$this->opcode] ?? 'WEBSOCKET_BAD_OPCODE';
    }

    public function getOpcode()
    {
        return $this->opcode;
    }

    public function getData()
    {
        return $this->data;
    }

    public function __toString()
    {
        return $this->data;
    }

}
