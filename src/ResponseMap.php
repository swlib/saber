<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/30 下午11:35
 */

namespace Swlib\Saber;

use ArrayObject;
use InvalidArgumentException;

class ResponseMap extends ArrayObject
{

    public $time = 0.000;
    public $status_map = [];
    public $success_map = [];
    public $success_num = 0;
    public $error_num = 0;

    public function __construct($responses = [])
    {
        parent::__construct($responses);
    }

    public function offsetSet($index, $response)
    {
        if (!($response instanceof Response)) {
            throw new InvalidArgumentException("Value must be instance of " . Response::class);
        }
        parent::offsetSet($index, $response);
        $this->time = $this->time ?: max($this->time, $response->getTime());
        $this->status_map[$index] = $response->getStatusCode();
        $success = $response->getSuccess();
        $this->success_map[$index] = $success;
        $success ? $this->success_num++ : $this->error_num++;
    }

    public function __toString()
    {
        $results = [];
        foreach ($this as $response) {
            $results[] = (string)$response;
        }

        return implode("\r\n\r\n\r\n\r\n", $results);
    }

}
