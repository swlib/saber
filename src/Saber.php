<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/3/24 下午8:43
 */

namespace Swlib;

use BadMethodCallException;
use Swlib\Http\ContentType;
use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Http\SwUploadFile;
use Swlib\Http\Uri;
use Swlib\Saber\Request;
use Swlib\Saber\RequestQueue;
use Swlib\Saber\Response;
use Swlib\Saber\ResponseMap;
use Swlib\Saber\WebSocket;
use Swlib\Util\DataParser;
use Swlib\Util\TypeDetector;
use function Swlib\Http\stream_for;

class Saber
{

    private static $default_options = [
        'timeout' => 5.000,
        'proxy' => null,
        'ssl' => Request::SSL_AUTO,
        'cafile' => __DIR__ . '/cacert.pem',
        'ssl_verify_peer' => false,
        'ssl_host_name' => null,
        'ssl_allow_self_signed' => true,
        'protocol_version' => '1.1',
        'method' => 'GET',
        'base_uri' => null,
        'uri' => null,
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Encoding' => 'gzip'
        ],
        'cookies' => false,
        'useragent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36',
        'referer' => '',
        'content_type' => ContentType::QUERY,
        'redirect' => 3,
        'keep_alive' => true,
        'uri_query' => null,
        'data' => null,
        'json' => null,
        'query' => null,
        'xml' => null,
        'files' => [],
        'before' => [],
        'after' => [],
        'after_co' => [],
        'before_redirect' => [],
        'iconv' => ['auto', 'utf-8', false],
        'max_co' => -1,
        'exception_report' => HttpExceptionMask::E_ALL,
        'exception_handle' => [],
        'retry' => [],
        'retry_time' => 0,
        'use_pool' => false
    ];

    private static $aliasMapLength;
    private static $aliasMap = [
        0 => 'method',
        1 => 'uri',
        2 => 'data',
        'base_url' => 'base_uri',
        'url' => 'uri',
        'callback' => 'after',
        'content-type' => 'content_type',
        'contentType' => 'content_type',
        'cookie' => 'cookies',
        'header' => 'headers',
        'follow' => 'redirect',
        'ua' => 'useragent',
        'user-agent' => 'useragent',
        'body' => 'data',
        'error_report' => 'exception_report',
        'report' => 'exception_report',
        'retry' => 'before_retry',
        'ref' => 'referer',
        'referrer' => 'referer'
    ];

    private static $aliasMapOfRequestsLength;
    private static $aliasMapOfRequests = [
        'concurrency' => 'max_co'
    ];

    private static $default_template_request;

    private $raw;
    private $options = [];
    private $session = false;
    private $_wait = false;

    /******************************************************************************
     *                             Static Methods                                 *
     ******************************************************************************/

    public static function create(array $options = []): self
    {
        return new static($options);
    }

    public static function session(array $options = []): self
    {
        $client = new static();
        $client->session = true;
        $options = self::mergeOptions($options, [
            'before' => [
                function (Request $request) use ($client) {
                    $request->cookies->adds($client->raw->cookies);
                }
            ],
            'after' => [
                function (Response $response) use ($client) {
                    $client->raw->cookies->adds($response->cookies);
                    $client->raw->incremental_cookies->adds($response->cookies);
                }
            ]
        ]);

        return $client->setOptions($options);
    }

    public static function websocket(string $uri): WebSocket
    {
        return self::create(['uri' => $uri])->upgrade();
    }

    /******************************************************************************
     *                             Normal Methods                                 *
     ******************************************************************************/

    private function __construct(array $options = [])
    {
        $this->setOptions(
            $this->options = $options,
            $this->raw = $this->getTemplateRequestCopy()
        );
    }

    /**
     * @param array $options
     * @return Request|Response
     */
    public function request(array $options)
    {
        $request = clone $this->raw;
        $this->setOptions($options, $request);

        /** Psr style */
        if ($options['psr'] ?? false) {
            return $request;
        }

        $request->exec();

        if ($options['wait'] ?? false || $this->_wait) {
            $this->_wait = false;
            return $request;
        }

        return $request->recv();
    }

    public function get(string $uri, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'GET';

        return $this->request($options);
    }

    public function delete(string $uri, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'DELETE';

        return $this->request($options);
    }

    public function head(string $uri, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'HEAD';

        return $this->request($options);
    }

    public function options(string $uri, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'OPTIONS';

        return $this->request($options);
    }

    public function post(string $uri, $data = null, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'POST';
        if ($data !== null) {
            $options['data'] = $data;
        }

        return $this->request($options);
    }

    public function put(string $uri, $data = null, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'PUT';
        if ($data !== null) {
            $options['data'] = $data;
        }

        return $this->request($options);
    }

    public function patch(string $uri, $data = null, array $options = [])
    {
        $options['uri'] = $uri;
        $options['method'] = 'PATCH';
        if ($data !== null) {
            $options['data'] = $data;
        }

        return $this->request($options);
    }

    public function download(string $uri, string $dir, int $offset = 0, array $options = [])
    {
        return $this->request([
                'uri' => $uri,
                'method' => 'GET',
                'download_dir' => $dir,
                'download_offset' => $offset
            ] + $options
        );
    }

    /**
     * Multi requests
     *
     * @param array $requests
     * @param array $default_options
     * @return ResponseMap|Response[]
     */
    public function requests(array $requests, array $default_options = []): ResponseMap
    {
        $req_queue = new RequestQueue(); //生成请求队列
        self::transAlias(
            $default_options,
            self::$aliasMapOfRequests,
            self::$aliasMapOfRequestsLength ?? self::$aliasMapOfRequestsLength = count(self::$aliasMapOfRequests)
        );
        $default_options = self::mergeOptions($default_options, $this->options);
        if ($max_co = $default_options['max_co'] ?? false) {
            $req_queue->withMaxConcurrency($max_co);
        }
        if ($default_options['after_co'] ?? false) {
            $req_queue->withAddedInterceptor('after_concurrency', (array)$default_options['after_co']);
        }
        foreach ($requests as $index => $request_options) {
            $request_instance = clone $this->raw;
            $request_options = self::mergeOptions($request_options, $default_options);
            $this->setOptions($request_options, $request_instance);
            $req_queue->enqueue($request_instance);
        }

        return $req_queue->recv();
    }

    /**
     * @param array $options
     * @param array $default_options
     * @return ResponseMap|Response[]
     */
    public function list(array $options, array $default_options = []): ResponseMap
    {
        $new = [];
        foreach ($options as $name => $option) {
            foreach ($option as $index => $value) {
                $new[$index][$name] = $value;
            }
        }

        return $this->requests($new, $default_options);
    }

    public function upgrade(string $path = null): WebSocket
    {
        $uri = Uri::resolve(
            $this->options['base_uri'] ?? null,
            $this->options['uri'] ?? null
        );
        if (isset($path)) {
            $uri->withPath($path);
        }

        return new WebSocket($uri);
    }

    /******************************************************************************
     *                             Special Methods                                *
     ******************************************************************************/

    public function psr(array $options = []): Request
    {
        return $this->request(['psr' => true] + $options);
    }

    /** @return $this */
    public function wait(): self
    {
        $this->_wait = true;

        return $this;
    }

    /******************************************************************************
     *                             Options Methods                                *
     ******************************************************************************/

    public function exceptionReport(?int $level = null): ?int
    {
        if ($level === null) {
            return $this->options['exception_report'];
        } else {
            $this->setOptions(['exception_report' => $level]);
        }

        return null;
    }

    public function exceptionHandle(callable $handle): void
    {
        $this->setOptions(['exception_handle' => $handle]);
    }

    /******************************************************************************
     *                         Private Options Methods                            *
     ******************************************************************************/

    public static function getAliasMap(): array
    {
        return self::$aliasMap;
    }

    /**
     * @param array $options
     * @param null|Request $request
     * @return $this
     */
    public function setOptions(array $options = [], ?Request $request = null): self
    {
        if (empty($options)) {
            return $this;
        }
        if ($request === null || $request === $this->raw) {
            $request = $this->raw;
            $this->options += self::mergeOptions($options, $this->options);
        }

        // special options
        $options += [
            'base_uri' => $this->options['base_uri'] ?? null,
            'uri' => $this->options['uri'] ?? null
        ];
        if (empty($options['base_uri']) && !empty($options['uri'] && strpos($options['uri'], '://') === false)) {
            // fix uri like localhost
            $options['uri'] = "http://{$options['uri']}";
        }
        if ($this->session) {
            $options['session'] = $this->raw->cookies;
        }

        self::transOptionsToRequest($options, $request);

        return $this;
    }

    private static function getTemplateRequestCopy(): Request
    {
        if (!isset(self::$default_template_request)) {
            self::$default_template_request = new Request();
            self::transOptionsToRequest(
                self::$default_options,
                self::$default_template_request
            );
        }

        return clone self::$default_template_request;
    }

    public static function getDefaultOptions(): array
    {
        return self::$default_options;
    }

    public static function setDefaultOptions(array $options = [])
    {
        if (empty($options)) {
            return;
        }
        $options = self::mergeOptions($options, self::$default_options);
        self::$default_options = $options + self::$default_options;
        self::$default_template_request = null;
    }

    private static function transAlias(array &$options, array $aliasMap, int $aliasMapLength = 0)
    {
        if (!$aliasMapLength) {
            $aliasMapLength = count($aliasMap);
        }

        if (count($options) > $aliasMapLength) {
            foreach ($aliasMap as $alias => $raw_key) {
                if (isset($options[$alias]) && !isset($options[$raw_key])) {
                    $options[$raw_key] = &$options[$alias];
                }
            }
        } else {
            foreach ($options as $key => &$val) {
                if (isset($aliasMap[$key]) && !isset($options[$aliasMap[$key]])) {
                    $options[$aliasMap[$key]] = &$val;
                }
            }
        }
    }

    private static function transOptionsToRequest(array $options, Request $request)
    {
        if (empty($options)) {
            return;
        }
        self::transAlias(
            $options,
            self::$aliasMap,
            self::$aliasMapLength ?? self::$aliasMapLength = count(self::$aliasMap)
        );

        if (array_key_exists('exception_report', $options)) {
            if (is_bool($options['exception_report'])) {
                $options['exception_report'] =
                    $options['exception_report'] ?
                        HttpExceptionMask::E_ALL :
                        HttpExceptionMask::E_NONE;
            }
            $request->setExceptionReport($options['exception_report']);
        }

        if (isset($options['base_uri']) || isset($options['uri'])) {
            $request->withUri(
                Uri::resolve($options['base_uri'], $options['uri']),
                $request->hasHeader('Host')
            );
        }

        if (isset($options['use_pool'])) {
            $request->withPool($options['use_pool']);
        }

        if (!empty($options['uri_query']) && $uri = $request->getUri()) {
            $uri->withQuery(DataParser::toQueryString(($options['uri_query'])));
        }

        /** 设置请求方法 */
        if (isset($options['method'])) {
            $request->withMethod($options['method']);
        }

        /** （可能的）HTTPS 连接证书 */
        if (isset($options['ssl'])) {
            $request->withSSL($options['ssl']);
        }
        if (array_key_exists('ssl_allow_self_signed', $options)) {
            $request->withSSLAllowSelfSigned($options['ssl_allow_self_signed']);
        }
        if (array_key_exists('ssl_verify_peer', $options)) {
            $request->withSSLVerifyPeer(
                $options['ssl_verify_peer'],
                $options['ssl_host_name'] ?? ''
            );
        }

        /** 设置超时 */
        if (isset($options['timeout'])) {
            $request->withTimeout($options['timeout']);
        }

        /** 是否跟踪重定向 */
        if (isset($options['redirect'])) {
            $request->withRedirect($options['redirect']);
        }

        /** 设置请求标头 */
        if (!empty($options['headers'])) {
            if (is_array($options['headers'])) {
                $request->withHeaders($options['headers']);
            }
            // TODO: other types
        }

        if (!empty($options['auth'])) {
            $request->withBasicAuth($options['auth']['username'] ?? '', $options['auth']['password'] ?? '');
        }

        /** 设置COOKIE */
        if (!empty($options['cookies'])) {
            $cookies_instance = $options['session'] ?? $request->cookies;//FIXME
            $cookies_default = ($uri = $request->getUri()) ? ['domain' => $uri->getHost()] : [];
            //everything can be a Cookies object
            $cookies_instance->adds(
                $options['cookies'],
                $cookies_default,
                true
            );
        }

        /** 设置模拟的浏览器标识 */
        if (array_key_exists('useragent', $options)) {
            $request->withHeader('User-Agent', $options['useragent']);
        }

        /** 设置来源页面 */
        if (array_key_exists('referer', $options) && !empty($options['referer'])) {
            $request->withHeader('Referer', $options['referer']);
        }

        if (isset($options['keep_alive'])) {
            $request->withKeepAlive($options['keep_alive']);
        }

        /** Set special mark */
        if (isset($options['mark'])) {
            $request->withSpecialMark($options['mark']);
        }

        /** proxy 是否启用代理 */
        if (array_key_exists('proxy', $options)) {
            if ($options['proxy'] === null) {
                $request->withoutProxy();
            } else {
                $parse = parse_url($options['proxy']);
                if ($parse['scheme'] === 'socks5') {
                    $request->withSocks5(
                        $parse['host'], $parse['port'],
                        $parse['user'] ?? null, $parse['pass'] ?? null
                    );
                } else {
                    $request->withProxy(
                        $parse['host'], $parse['port'],
                        $parse['user'] ?? null, $parse['pass'] ?? null
                    );
                }
            }
        }

        if (!empty($options['json'])) {
            $options['content_type'] = ContentType::JSON;
            $options['data'] = &$options['json'];
        } elseif (!empty($options['query'])) {
            $options['content_type'] = ContentType::QUERY;
            $options['data'] = &$options['query'];
        } elseif (!empty($options['xml'])) {
            $options['content_type'] = ContentType::XML;
            $options['data'] = &$options['xml'];
        }

        /** 设置请求的数据 */
        if (array_key_exists('content_type', $options)) {
            $request->withHeader('Content-Type', $options['content_type']);
        }
        if (!empty($options['data'])) {
            if (!TypeDetector::canBeString($options['data'])) {
                switch ($request->getHeaderLine('Content-Type')) {
                    case ContentType::JSON:
                        $options['data'] = DataParser::toJsonString($options['data']);
                        break;
                    case ContentType::XML:
                        $options['data'] = DataParser::toXmlString($options['data']);
                        break;
                    case ContentType::MULTIPART:
                        $boundary = '----WebKitFormBoundary' . openssl_random_pseudo_bytes(16);
                        $request->withHeader('Content-Type', "multipart/form-data; boundary={$boundary}");
                        $options['data'] = DataParser::toMultipartString($options['data'], $boundary);
                        break;
                    case ContentType::QUERY:
                    default:
                        $options['data'] = DataParser::toQueryString($options['data']);
                }
            }
        } else {
            $options['data'] = null;
        }
        $buffer = $options['data'] ? stream_for((string)$options['data']) : null;
        if (isset($buffer)) {
            $request->withBody($buffer);
        }

        if (!empty($options['files'])) {
            if (key($options['files']) === 0) {
                throw new BadMethodCallException('File must has it\'s form field name! Such as {"file1": "~/foo.png"}}.');
            }
            foreach ($options['files'] as $form_field_name => $file) {
                $request->withUploadedFile($form_field_name, SwUploadFile::create($file));
            }
        }

        if (array_key_exists('iconv', $options)) {
            if (is_array($options['iconv'])) {
                $options['iconv'] = $options['iconv'] + self::$default_options['iconv'];
                $request->withExpectCharset(...$options['iconv']);
            } else {
                $request->withAutoIconv($options['iconv'] !== false);
            }
        }

        /** download mode */
        if (!empty($options['download_dir'])) {
            $request
                ->withDownloadDir((string)$options['download_dir'])
                ->withDownloadOffset((int)$options['download_offset']);
        }

        if (isset($options['retry_time'])) {
            $request->withRetryTime($options['retry_time']);
        }
        /** register interceptor before every retry */
        if (!empty($options['retry'])) {
            if ($request->getRetryTime() < 1) {
                $request->withRetryTime(1);
            }
            $request->withAddedInterceptor('before_retry', (array)$options['before_retry']);
        }

        /** register Interceptor before request */
        if (!empty($options['before'])) {
            $request->withAddedInterceptor('request', (array)$options['before']);
        }

        /** register callback (after response)  */
        if (!empty($options['after'])) {
            $request->withAddedInterceptor('response', (array)$options['after']);
        }

        /** register interceptor before every redirects */
        if (!empty($options['before_redirect'])) {
            $request->withAddedInterceptor('before_redirect', (array)$options['before_redirect']);
        }

        if (!empty($options['exception_handle'])) {
            $request->withAddedInterceptor('exception', (array)$options['exception_handle']);
        }
    }

    private static function mergeOptions(array $options, ... $defaults)
    {
        static $special_fields;
        static $special_fields_length;
        if (!isset($special_fields)) {
            $special_fields = [];
            foreach (self::$default_options as $key => $val) {
                // dict-array but not array
                if (is_array($val) && key($val) !== 0) {
                    $special_fields[$key] = $key;
                }
            }
            $special_fields_length = count($special_fields);
        }
        foreach ($defaults as $default) {
            if (!is_array($default)) {
                continue;
            }
            if (count($options) > $special_fields_length) {
                foreach ($special_fields as $field) {
                    if (isset($options[$field])) {
                        $options[$field] = array_merge((array)($default[$field] ?? []), (array)$options[$field]);
                    }
                }
            } else {
                foreach ($options as $key => $val) {
                    if (isset($special_fields[$key])) {
                        $options[$key] = array_merge((array)($default[$key] ?? []), (array)$options[$key]);
                    }
                }
            }
            $options += $default;
        }
        //FIXME: if get string header, we must trans it to array, but it's too difficult.

        return $options;
    }

    // private static function mergeData($default, $add): array
    // {
    //     if (is_string($default)) {
    //         parse_str($default, $default);
    //     }
    //     if (is_string($add)) {
    //         parse_str($add, $add);
    //     }
    //
    //     /** @var $add array */
    //     /** @var $default array */
    //     return $add + $default;
    // }

}
