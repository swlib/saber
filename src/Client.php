<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/24 下午8:43
 */

namespace Swlib\Saber;

use Swlib\Http\BufferStream;
use Swlib\Http\ContentType;
use Swlib\Http\Uri;

/**
 * @method Request|Response get(string | string $uri, array $options = [])
 * @method Request|Response head(string | string $uri, array $options = [])
 * @method Request|Response put(string | string $uri, $data = null, array $options = [])
 * @method Request|Response post(string | string $uri, $data = null, array $options = [])
 * @method Request|Response patch(string | string $uri, $data = null, array $options = [])
 * @method Request|Response delete(string | string $uri, array $options = [])
 */
class Client
{

    private static $default_options_multi_fields = [
        'headers',
    ];
    private static $default_options = [
        'connect_timeout' => 3, //连接超时时间
        'timeout' => 5,
        'proxy' => null,
        'ssl' => false,
        'ssl_verify' => true,
        'cafile' => __DIR__ . '/cacert.pem',
        'protocol_version' => '1.1',
        'method' => 'GET',
        'base_uri' => null,
        'uri' => null,
        'headers' => [
            'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'accept-encoding' => 'gzip',
        ],
        'cookies' => false,
        'useragent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        'content_type' => ContentType::URLENCODE,
        'redirect' => 5,
        'keep_alive' => true,
        'data' => null,
    ];

    public static function create(array $options = []): self
    {
        return new static(
            self::mergeOptions($options, self::$default_options, self::$default_options_multi_fields)
        );
    }

    public $options = [];

    private function __construct(array $options)
    {
        $this->options = $options;
    }

    private static function mergeOptions(array $options, array $default, array $multi_fields_keys = [])
    {
        /** merge default options */
        //array fields would be overwrite without doing these
        foreach ($multi_fields_keys as $key) {
            if (isset($options[$key])) {
                $options[$key] += self::$default_options[$key];
            }
        }

        return $options + $default;
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments[0])) {
            throw new \InvalidArgumentException('Uri should not be empty!');
        }
        $def = [
            'uri' => $arguments[0],
            'method' => $name,
        ];
        $name = strtoupper($name);
        switch ($name) {
            case 'GET':
            case 'HEAD':
            case 'DELETE':
                {
                    $options = $arguments[1] ?? [];

                    return $this->request($def + $options);
                }
            case 'POST':
            case 'PUT':
            case 'PATCH':
                {
                    if (isset($arguments[1])) {
                        $def += ['data' => $arguments[1]];
                    }
                    $options = $arguments[2] ?? [];

                    return $this->request($def + $options);
                }
            default:
                throw new \BadMethodCallException("Method '{$name}' not exist!");
        }
    }

    /**
     * @param array $options
     * @return Request|Response
     */
    public function request(array $options)
    {
        $options = self::mergeOptions($options, $this->options, self::$default_options_multi_fields);
        $uri = Uri::resolve($options['base_uri'], $options['uri']);

        /**
         * 设置请求的数据和method
         */
        $options['method'] = strtoupper($options['method']);
        if (!empty($options['data'])) {
            if (!is_string($options['data'])) {
                switch ($options['content_type']) {
                    case ContentType::JSON:
                        $options['data'] = json_encode($options['data']);
                        break;
                    case ContentType::XML:
                        throw new \BadMethodCallException('XML-encoder not implemented');
                        break;
                    case ContentType::URLENCODE:
                    default:
                        $options['data'] = http_build_query($options['data']);
                }
            }
        } else {
            $options['data'] = null;
        }
        $buffer = $options['data'] ? new BufferStream($options['data']) : null;

        $request = new Request($uri, $options['method'], [], $buffer);

        /**
         * （可能的）HTTPS 连接证书
         */
        if ($uri->getPort() === 443 || $options['ssl']) {
            $request->withSSL(true, $options['cafile']);
        }

        /**
         * 设置两个超时选项
         */
        if (isset($options['timeout'])) {
            $request->withTimeout($options['timeout']);
        }

        /**
         * 是否跟踪重定向
         */
        if (isset($options['redirect'])) {
            $request->withRedirect($options['redirect']);
        }

        /**
         * 设置请求标头
         */
        if (isset($options['headers'])) {
            foreach ($options['headers'] as $key => $val) {
                if (is_numeric($key)) {
                    $options['headers'] = Util::parseHeader($options['headers']); //TODO
                } else {
                    $request->withHeader($key, $val);
                }
            }
        }

        /**
         * 设置COOKIE
         */
        if (!empty($options['cookies'])) {
            //everything can be a Cookies object
            $request->cookies->adds($options['cookies']);
        }

        /**
         * 设置模拟的浏览器标识
         */
        if (isset($options['useragent'])) {
            $request->withHeader('User-Agent', $options['useragent']);
        }

        /**
         * 设置来源页面
         */
        if (isset($options['referer'])) {
            $request->withHeader('Referer', $options['referer']);
        }

        if (isset($options['keep_alive'])) {
            $request->withKeepAlive($options['keep_alive']);
        }

        /**
         * proxy 是否启用代理
         */
        if (isset($options['proxy'])) {
            $parse = parse_url($options['proxy']);
            if ($parse['scheme'] === 'socks5') {
                $request->withSocks5(
                    $parse['host'], $parse['port'],
                    $parse['user'] ?? null, $parse['pass'] ?? null
                );
            } else {
                $request->withProxy($parse['host'], $parse['port']);
            }
        }

        /**
         * 注册请求前前拦截器
         */
        if (isset($options['before'])) {
            $request->withInterceptor('request', $options['before']);
        }

        /**
         * 注册回调函数
         */
        if (isset($options['callback'])) {
            $request->withInterceptor('response', $options['callback']);
        }

        /**
         * Psr style
         */
        if ($options['psr'] ?? false) {
            return $request;
        }

        $request->exec();

        if ($options['wait'] ?? false) {
            return $request;
        }

        return $request->recv();
    }

    /** @return $this */
    public function wait(): self
    {
        $this->options['wait'] = true;

        return $this;
    }

    /** @return $this */
    public function psr()
    {
        $this->options['psr'] = true;

        return $this;
    }

    public static function merge_data($default, $add): array
    {
        if (is_string($default)) {
            parse_str($default, $default);
        }
        if (is_string($add)) {
            parse_str($add, $add);
        }

        /** @var $add array */
        /** @var $default array */
        return $add + $default;
    }

    /**
     * 使用延迟收包发起一个并发请求
     *
     * @param array $requests
     * @param array $_options
     * @param array $data
     *
     * @return ResponseMap
     */
    public function requests(array $requests, array $default_options = []): ResponseMap
    {
        $req_queue = new RequestQueue(); //生成请求队列
        foreach ($requests as $index => $options) {
            $options = self::mergeOptions($options, $default_options);
            $req_queue->enqueue($this->psr()->request($options));
        }

        return $req_queue->recv();
    }

}