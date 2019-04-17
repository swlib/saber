<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/6/29 下午11:49
 */

namespace Swlib\Saber;

use BadMethodCallException;
use InvalidArgumentException;
use Swlib\Util\MapPool;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;

class ClientPool extends MapPool
{

    public function createEx(array $options, bool $temp = false)
    {
        if (Coroutine::getuid() < 0) {
            throw new BadMethodCallException(
                'You can only use coroutine client in `go` function or some Event callback functions.' . PHP_EOL .
                'Please check https://wiki.swoole.com/wiki/page/696.html'
            );
        }
        $client = new Client($options['host'], $options['port'], $options['ssl']);
        if ($temp) {
            return $client; // not record
        } else {
            $key = "{$options['host']}:{$options['port']}";
            parent::create($options, $key);
            return $client;
        }
    }

    public function setMaxEx(array $options, int $max_size = -1): int
    {
        $key = "{$options['host']}:{$options['port']}";
        $ret = parent::setMax($key, $max_size);
        if ($ret === -1) { // chan reduce max size
            $chan = $this->resource_map[$key];
            $current_max = $this->getMax($key);
            $current_num = $chan->length();
            while ($current_num-- > $current_max) {
                /** @var $client Client */
                $client = $chan->pop();
                if ($client->connected) {
                    $client->close();
                }
                unset($client);
            }
        }
        return $ret;
    }

    public function getEx(string $host, string $port): ?Client
    {
        /** @var $client Client */
        $key = "{$host}:{$port}";
        $client = parent::get($key);
        if ($client && SABER_SW_LE_V401 && !$client->isConnected()) {
            @$this->status_map[$key]['disconnected']++;
            $client->close(); // clear hcc to prevent not active warn in swoole ver <= 4.0.1
        }
        return $client;
    }

    public function putEx(Client $client)
    {
        /** @var $client Client */
        if (!($client instanceof Client)) {
            throw new InvalidArgumentException('$value should be instance of ' . Client::class);
        }
        parent::put($client, "{$client->host}:{$client->port}");
    }

    public function destroyEx(Client $client)
    {
        $client->close();
        parent::destroy($client, "{$client->host}:{$client->port}");
    }

    public function release(string $key)
    {
        $pool = $this->resource_map[$key] ?? null;
        if ($pool) {
            while (!$pool->isEmpty()) {
                /** @var $client Client */
                $client = $pool->pop();
                if ($client->connected) {
                    $client->close();
                }
            }
            if ($pool instanceof Channel) {
                $pool->close();
            }
        }
        $this->resource_map[$key] = $this->status_map[$key] = null;
    }

    public function releaseAll()
    {
        foreach ($this->resource_map as $key => $_) {
            $this->release($key);
        }
    }

}

