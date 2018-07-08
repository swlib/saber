<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/6/29 下午11:49
 */

namespace Swlib\Saber;

use Swoole\Coroutine\Http\Client;

class ClientPool extends \Swlib\Util\MapPool
{

    public function create(array $options, string $key = null)
    {
        if (\Swoole\Coroutine::getuid() < 0) {
            throw  new \BadMethodCallException(
                'You can only use coroutine client in `go` function or some Event callback functions.' . PHP_EOL .
                'Please check https://wiki.swoole.com/wiki/page/696.html'
            );
        }
        $client = new Client($options['host'], $options['port'], $options['ssl']);
        if (!isset($key)) {
            $key = "{$options['host']}:{$options['port']}";
        }
        parent::create($options, $key);

        return $client;
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
        return parent::get("{$host}:{$port}");
    }

    public function putEx(Client $client)
    {
        /** @var $client Client */
        if (!($client instanceof Client)) {
            throw new \InvalidArgumentException('$value should be instance of ' . Client::class);
        }
        parent::put($client, "{$client->host}:{$client->port}");
    }

    public function clean(string $key)
    {
        $chan = $this->resource_map[$key] ?? null;
        if ($chan) {
            while (!$chan->isEmpty()) {
                /** @var $client Client */
                $client = $chan->pop();
                if ($client->connected) {
                    $client->close();
                }
            }
        }
        return;
    }

}

