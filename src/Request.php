<?php
/**
 * Copyright: Swlib
 * Author: Twosee <twose@qq.com>
 * Date: 2018/1/9 下午3:19
 */

namespace Swlib\Saber;

use BadMethodCallException;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use Swlib\Http\Cookies;
use Swlib\Http\CookiesManagerTrait;
use Swlib\Http\Exception\ConnectException;
use Swlib\Http\Exception\HttpExceptionMask;
use Swlib\Http\StreamInterface;
use Swlib\Http\SwUploadFile;
use Swlib\Http\Uri;
use Swlib\Util\InterceptorTrait;
use Swlib\Util\SpecialMarkTrait;
use Swoole\Coroutine\Http\Client;

class Request extends \Swlib\Http\Request
{
    public const STATUS_NONE = 1;
    public const STATUS_WAITING = 2;

    protected const FROM_REDIRECT = 1 << 1;
    protected const FROM_RETRY = 1 << 2;

    /** @var $client Client */
    public $client;
    /** @var bool must be changed through method */
    protected $use_pool = false;

    public $exception_report = HttpExceptionMask::E_ALL;

    const SSL_OFF = 0;
    const SSL_ON = 1;
    const SSL_AUTO = 2;
    /** @var int 是否使用SSL连接 */
    public $ssl = self::SSL_AUTO;
    /** @var string CA证书目录 */
    public $ca_file = '';
    public $ssl_verify_peer = false;
    public $ssl_host_name = '';
    public $ssl_allow_self_signed = true;

    /** @var array 代理配置 */
    public $proxy = [];
    /** @var int IO超时时间 */
    public $timeout = 3;
    /** @var int 最大重定向次数,为0时关闭 */
    public $redirect = 3;
    /** @var bool 是否在队列中,如为时, 优化策略会在重定向时等待 */
    public $in_queue = false;
    /**@var bool 长连接 */
    public $keep_alive = true;
    /** @var int 自动重试次数 */
    public $retry_time = 0;

    /** @var string where download to */
    protected $download_dir = '';
    protected $download_offset = 0;

    public $auto_iconv = true;
    public $charset_source;
    public $charset_target;
    public $charset_use_mb = false;

    /** @var int client status */
    public $_status = self::STATUS_NONE;
    /** @var float request start micro time */
    public $_start_time;
    /** @var float timeout left */
    public $_timeout;
    /** @var int consuming time */
    public $_time = 0.000;
    /** @var int 已重定向次数 */
    public $_redirect_times = 0;
    /** @var array 重定向的headers */
    public $_redirect_headers = [];
    /** @var int 已重试次数 */
    public $_retried_time = 0;

    /** @var int internal flag */
    protected $_form_flag = 0;

    use CookiesManagerTrait;

    use InterceptorTrait;

    use SpecialMarkTrait;

    function __construct(string $method = 'GET', $uri = '', array $headers = [], ?StreamInterface $body = null)
    {
        parent::__construct($method, $uri, $headers, $body);
        $this->__constructCookiesManager(true);
        $this->initBasicAuth();
    }

    /**
     * @param UriInterface|null $uri
     * @param bool $preserveHost
     * @return \Swlib\Http\Request
     */
    public function withUri(?UriInterface $uri, $preserveHost = false): \Swlib\Http\Request
    {
        if ($uri !== $this->uri) {
            $this->uri = $uri;
        }

        if (!$preserveHost) {
            $this->updateHostFromUri();
        }

        if (!$this->hasHeader('Authorization')) {
            $this->initBasicAuth();
        }

        return $this;
    }

    private function updateHostFromUri()
    {
        $host = $this->uri->getHost();
        if ($host == '') {
            return;
        }
        if (($port = $this->uri->getPort()) !== null) {
            $host .= ':' . $port;
        }
        if (isset($this->headerNames['host'])) {
            $raw_name = $this->headerNames['host'];
        } else {
            $raw_name = 'Host';
            $this->headerNames['host'] = 'Host';
        }

        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $this->headers = [$raw_name => [$host]] + $this->headers;
    }

    public function getExceptionReport(): int
    {
        return $this->exception_report;
    }

    public function setExceptionReport(int $level): self
    {
        $this->exception_report = $level;

        return $this;
    }

    public function isWaiting(): bool
    {
        return $this->_status === self::STATUS_WAITING;
    }

    protected function initBasicAuth()
    {
        $userInfo = $this->getUri()->getUserInfo();
        if ($userInfo) {
            $userInfo = explode(':', $userInfo);
            $username = $userInfo[0];
            $password = $userInfo[1] ?? null;
            $this->withBasicAuth($username, $password);
        }
    }

    public function getConnectionTarget(): array
    {
        $host = $this->uri->getHost();
        if (empty($host)) {
            throw new InvalidArgumentException('Host should not be empty!');
        }
        $port = $this->uri->getRealPort();
        $ssl = $this->getSSL();
        $ssl = ($ssl === self::SSL_AUTO) ? ('https' === $this->uri->getScheme()) : (bool) $ssl;

        return ['host' => $host, 'port' => $port, 'ssl' => $ssl];
    }

    public function shouldRecycleClient($client)
    {
        $connectionInfo = $this->getConnectionTarget();

        return (!$client || ($client->host !== $connectionInfo['host'] || $client->port !== $connectionInfo['port']));
    }

    /** @return null|bool */
    public function getPool()
    {
        return $this->use_pool;
    }

    /**
     * @param $bool_or_max_size bool|int
     * @return $this
     */
    public function withPool($bool_or_max_size): self
    {
        if ($bool_or_max_size < 0) {
            $bool_or_max_size = true; // translate to unlimited
        }
        $this->use_pool = $bool_or_max_size;
        if (is_numeric($this->use_pool)) {
            // limit max num
            ClientPool::getInstance()->setMaxEx($this->getConnectionTarget(), $bool_or_max_size);
        }
        if ($bool_or_max_size) {
            $this->withKeepAlive(true);
        }

        return $this;
    }

    public function tryToRevertClientToPool(bool $connect_failed = false)
    {
        if ($this->use_pool) {
            $client_pool = ClientPool::getInstance();
            // revert the client to the pool
            if (SABER_SW_LE_V401) {
                if ($connect_failed || $this->isInQueue()) {
                    // in ver <= 4.0.1 when connect failed we must create new one
                    // in ver <= 4.0.1 (https://github.com/swoole/swoole-src/pull/1790)
                    // swoole have a bug about defer client and auto reconnect
                    // so we can't reuse it anymore.
                    $client_pool->destroyEx($this->client);
                }
            } else {
                $client_pool->putEx($this->client);
            }
        } else {
            // it will be left
            $this->client->close();
        }
        $this->client = null;
    }

    /**
     * 是否为SSL连接
     *
     * @return null|bool
     */
    public function getSSL(): int
    {
        return $this->ssl;
    }

    /**
     * enable/disable ssl and set a ca file.
     *
     * @param bool $mode
     * @param string $ca_file
     * @return $this
     */
    public function withSSL(int $mode = self::SSL_AUTO): self
    {
        $this->ssl = $mode;

        return $this;
    }

    public function getCAFile(): string
    {
        return $this->ca_file;
    }

    public function withCAFile(string $ca_file = __DIR__ . '/cacert.pem'): self
    {
        $this->ca_file = $ca_file;

        return $this;
    }

    public function withSSLVerifyPeer(bool $verify_peer = false, ?string $ssl_host_name = ''): self
    {
        $this->ssl_verify_peer = $verify_peer;
        if ($this->ssl_verify_peer && $ssl_host_name) {
            $this->ssl_host_name = $ssl_host_name;
        }

        return $this;
    }

    public function withSSLAllowSelfSigned(bool $allow = true): self
    {
        $this->ssl_allow_self_signed = $allow;

        return $this;
    }

    public function getSSLConf()
    {
        return
            [
                'ssl_cafile' => $this->getCAFile(),
                'ssl_allow_self_signed' => $this->ssl_allow_self_signed,
            ] + (
            $this->ssl_verify_peer ? [
                'ssl_verify_peer' => $this->ssl_verify_peer,
                'ssl_host_name' => $this->ssl_host_name ?: $this->uri->getHost()
            ] : []
            );
    }

    public function getKeepAlive()
    {
        return $this->keep_alive;
    }

    /**
     * @param bool $enable
     * @return $this
     */
    public function withKeepAlive(bool $enable): self
    {
        $this->keep_alive = $enable;

        return $this;
    }

    /**
     * Add basic authorization header
     *
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function withBasicAuth(?string $username = null, string $password = null): self
    {
        if ($username === null) {
            return $this->withoutHeader('Authorization');
        } else {
            $auth = base64_encode($username . ':' . $password);

            return $this->withHeader('Authorization', "Basic {$auth}");
        }
    }

    public function withXHR(bool $enable = true)
    {
        return $this->withHeader('X-Requested-With', $enable ? 'XMLHttpRequest' : null);
    }

    /**
     * 获得当前代理配置
     *
     * @return array
     */
    public function getProxy(): array
    {
        return $this->proxy;
    }

    /**
     * 配置HTTP代理
     *
     * @param string $host
     * @param int $port
     * @param null|string $username
     * @param null|string $password
     * @return $this
     */
    public function withProxy(string $host, int $port, ?string $username = null, ?string $password = null): self
    {
        $this->proxy = [
            'http_proxy_host' => $host,
            'http_proxy_port' => $port,
            'http_proxy_user' => $username,
            'http_proxy_password' => $password,
        ];

        return $this;
    }

    /**
     * enable socks5 proxy
     * @param string $host
     * @param int $port
     * @param null|string $username
     * @param null|string $password
     * @return $this
     */
    public function withSocks5(string $host, int $port, ?string $username, ?string $password): self
    {
        $this->proxy = [
            'socks5_host' => $host,
            'socks5_port' => $port,
            'socks5_username' => $username,
            'socks5_password' => $password,
        ];

        return $this;
    }

    /**
     * Remove proxy config
     * @return $this
     */
    public function withoutProxy(): self
    {
        $this->proxy = [];

        return $this;
    }

    /**
     * 获取超时时间
     *
     * @return float
     */
    public function getTimeout(): float
    {
        return $this->timeout;
    }

    /**
     * 设定超时时间
     *
     * @param float $timeout
     * @return $this
     */
    public function withTimeout(float $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * 获取重定向次数
     *
     * @return int
     */
    public function getRedirect(): int
    {
        return $this->redirect;
    }

    /** @return mixed */
    public function getName()
    {
        return $this->getSpecialMark('name');
    }

    /**
     * @param $name
     * @return $this
     */
    public function withName($name): self
    {
        $this->withSpecialMark($name, 'name');

        return $this;
    }

    /**
     * 设置最大重定向次数,为0则不重定向
     *
     * @param int $time
     * @return $this
     */
    public function withRedirect(int $time): self
    {
        $this->redirect = $time;

        return $this;
    }

    /** @return bool */
    public function isInQueue(): bool
    {
        return $this->in_queue;
    }

    /**
     * @param bool $enable
     * @return $this
     */
    public function withInQueue(bool $enable): self
    {
        $this->in_queue = $enable;

        return $this;
    }

    public function getRetryTime(): int
    {
        return $this->retry_time;
    }

    public function withRetryTime(int $time): self
    {
        $this->retry_time = $time;

        return $this;
    }

    public function getRetriedTime(): int
    {
        return $this->_retried_time;
    }

    public function withAutoIconv(bool $enable): self
    {
        $this->auto_iconv = $enable;

        return $this;
    }

    public function withExpectCharset(string $source = 'auto', string $target = 'utf-8', bool $use_mb = false): self
    {
        $this->charset_source = $source;
        $this->charset_target = $target;
        $this->charset_use_mb = (bool) $use_mb;

        return $this;
    }

    public function withDownloadDir(string $dir): self
    {
        $this->download_dir = $dir;

        return $this;
    }

    public function withDownloadOffset(int $offset): self
    {
        $this->withHeader('Range', $offset > 0 ? "bytes={$offset}-" : null)
            ->download_offset = $offset;

        return $this;
    }

    /**
     * Clear the swoole client to make it back to the first state.
     *
     * @param $client
     */
    public function resetClient($client)
    {
        //TODO
    }

    /**
     * 执行当前Request
     *
     * @return $this|mixed
     */
    public function exec()
    {
        /** reset temp attributes */
        if (!($this->_form_flag & self::FROM_REDIRECT)) {
            $this->clear();
        }

        /** interceptor after request */
        $ret = $this->callInterceptor('request', $this);
        if ($ret !== null) {
            return $ret;
        }

        if ($this->client && ($this->shouldRecycleClient($this->client))) {
            // target maybe changed
            $this->tryToRevertClientToPool();
        }
        if (!$this->client) {
            /** get connection info */
            $connectionInfo = $this->getConnectionTarget();
            /** create a new coroutine client */
            $client_pool = ClientPool::getInstance();
            if ($this->use_pool && $client = $client_pool->getEx($connectionInfo['host'], $connectionInfo['port'])) {
                $this->client = $client;
            } else {
                $this->client = $client_pool->createEx($connectionInfo, !$this->use_pool);
            }
        }

        /** Clear useless cookies property */
        $this->client->cookies = null;

        /** Set request headers */
        $cookie = $this->cookies->toRequestString($this->uri);

        // Ensure Host is the first header.
        // See: http://tools.ietf.org/html/rfc7230#section-5.4
        $headers = ['Host' => $this->getHeaderLine('Host') ?: $this->uri->getHost()] +
            $this->getHeaders(true, true);
        if (!empty($cookie) && empty($headers['Cookie'])) {
            $headers['Cookie'] = $cookie;
        }
        $this->client->setHeaders($headers);

        /** Set method */
        $this->client->setMethod($this->getMethod());
        /** Set Upload file */
        $files = $this->getUploadedFiles();
        if (!empty($files)) {
            /** @var $file SwUploadFile */
            foreach ($files as $key => $file) {
                $file_options = [
                    $file->getFilePath(),
                    $key
                ];
                if ($file_type = $file->getClientMediaType()) {
                    $file_options[] = $file_type;
                }
                if ($filename = $file->getClientFilename()) {
                    $file_options[] = $filename;
                }
                if ($file_offset = $file->getOffset()) {
                    $file_options[] = $file_offset;
                }
                if ($file_size = $file->getSize()) {
                    $file_options[] = $file_size;
                }
                $this->client->addFile(...$file_options);
            }
        }
        /** 设置请求主体 */
        $body = (string) ($this->getBody() ?? '');
        if ($body !== '') {
            $this->client->setData($body);
        }

        /** calc timeout value */
        if ($this->_redirect_times > 0) {
            $timeout = $this->getTimeout() - (microtime(true) - $this->_start_time);
            //TODO timeout exception
        } else {
            $this->_start_time = microtime(true);
            $timeout = $this->getTimeout();
        }
        $this->_timeout = max($timeout, 0.001); //swoole support min 1ms

        /** 设定配置项 */
        $settings = [
            'timeout' => (($this->_form_flag & self::FROM_REDIRECT) && $this->_timeout) ? $this->_timeout : $this->getTimeout(),
            'keep_alive' => $this->getKeepAlive(),
        ];

        if ($this->ssl) {
            $settings['ssl_host_name'] = $this->uri->getHost();
        }

        $settings += $this->getProxy();

        if (!empty($ca_file = $this->getCAFile())) {
            $settings += $this->getSSLConf();
        }
        $this->client->set($settings);

        /** Set defer and timeout */
        $this->client->setDefer(); //总是延迟回包以使用timeout定时器特性

        if (empty($this->download_dir)) {
            $this->client->execute($this->getRequestTarget());
        } else {
            $this->client->download($this->getRequestTarget(), $this->download_dir, $this->download_offset);
            // reset: download mode only once
            $this->download_dir = '';
            $this->download_offset = 0;
        }
        $this->_status = self::STATUS_WAITING;

        return $this;
    }

    /**
     * 收包,处理重定向,对返回数据进行处理
     *
     * @return Response|$this|mixed
     */
    public function recv()
    {
        _retry_recv:
        if (self::STATUS_WAITING !== $this->_status) {
            throw new BadMethodCallException('You can\'t recv because client is not in waiting stat.');
        }
        $this->client->recv($this->getTimeout());
        $this->_status = self::STATUS_NONE;
        $this->_time = microtime(true) - $this->_start_time;

        $is_report = $this->getExceptionReport() & HttpExceptionMask::E_CONNECT;
        $statusCode = $this->client->statusCode;
        $errCode = $this->client->errCode;
        if ($statusCode < 0 || $errCode !== 0) {
            if ($is_report) {
                if ($statusCode === -1) {
                    $message = 'Connect timeout! the server is not listening on the port or the network is missing!';
                } elseif ($statusCode === -2) {
                    $timeout = $this->getTimeout();
                    $message = "Request timeout! the server hasn't responded over the timeout setting({$timeout}s)!";
                } elseif ($statusCode === -3) {
                    $message = 'Connection is reset by the remote server';
                } else {
                    $message = "Linux Code {$errCode}: " . swoole_strerror($errCode);
                }
                $exception = new ConnectException($this, $statusCode, $message);
                $ret = $this->callInterceptor('exception', $exception);
                if (!$ret) {
                    $this->tryToRevertClientToPool(true);
                    throw $exception;
                }
            } else {
                // Exception is no longer triggered after an exception is ignored
                $this->setExceptionReport(HttpExceptionMask::E_NONE);
            }
        }

        //将服务器cookie添加到客户端cookie列表中去
        if (!empty($this->client->set_cookie_headers)) {
            $domain = $this->uri->getHost();
            //in URI, the path must end with '/', cookie path is just the opposite.
            $path = rtrim($this->uri->getDir(), '/');
            $this->incremental_cookies->adds(
                array_values($this->client->set_cookie_headers), [
                'domain' => $domain,
                'path' => $path,
            ]);
            $this->cookies->adds($this->incremental_cookies); //TODO: optimize
        }

        /** Solve redirect */
        if (($this->client->headers['location'] ?? false) && $this->_redirect_times < $this->redirect) {
            $current_uri = (string) $this->uri;
            //record headers before redirect
            $this->_redirect_headers[$current_uri] = PHP_DEBUG ?
                array_merge([], $this->client->headers) :
                $this->client->headers;
            $location = $this->client->headers['location'];
            $this->uri = Uri::resolve($this->uri, $location);
            if ($this->uri->getPort() === 443) {
                $this->withSSL(true);
            }
            // TODO: remove some secret information
            $this->withMethod('GET')
                ->withBody(null)
                ->withHeader('Host', $this->uri->getHost())
                ->withHeader('Referer', $current_uri)
                ->withoutInterceptor('request');

            /**
             * Redirect-interceptors have permission to release or intercept redirects,
             * just return a bool type value
             */
            $allow_redirect = true;
            $ret = $this->callInterceptor('before_redirect', $this);
            if ($ret !== null) {
                if (is_bool($ret)) {
                    $allow_redirect = $ret;
                } else {
                    return $ret;
                }
            }

            if ($allow_redirect) {
                $this->_form_flag |= self::FROM_REDIRECT;
                $this->exec();
                $this->_redirect_times++;

                if ($this->isInQueue()) {
                    return $this;
                }

                return $this->recv();
            } else {
                $this->setExceptionReport(
                    $this->getExceptionReport() ^ HttpExceptionMask::E_REDIRECT
                );
            }
        }

        /** create response object */
        $response = new Response($this);

        /** call response interceptor */
        $ret = $this->callInterceptor('response', $response, $this);
        if ($ret !== null) {
            return $ret;
        }

        /** auto retry */
        while (!$response->getSuccess() && $this->_retried_time++ < $this->retry_time) {
            $ret = $this->callInterceptor('before_retry', $this, $response);
            if ($ret === false) {
                break;
            }
            $this->_form_flag |= self::FROM_RETRY;
            $this->exec();
            if ($this->isInQueue()) {
                return $this;
            } else {
                goto _retry_recv;
            }
        }

        // clear native client
        if (SABER_HCP_NEED_CLEAR) {
            $this->client->headers = [];
            $this->client->set_cookie_headers = [];
            $this->client->cookies = [];
        }
        $this->client->body = '';

        $this->tryToRevertClientToPool();

        return $response;
    }

    /**
     * clear tmp arguments
     */
    protected function clear()
    {
        $this->_redirect_times = 0;
        $this->_redirect_headers = [];
        $this->_start_time = 0;
        $this->_time = 0.000;
        if (!($this->_form_flag) & self::FROM_RETRY) {
            $this->_retried_time = 0;
        }
        $this->incremental_cookies->reset();

        // should be at the end of the clear
        $this->_form_flag = 0;
    }

    /**
     * Clear after clone
     */
    public function __clone()
    {
        $this->client = null;
        if ($this->_status === self::STATUS_WAITING) {
            $this->exec(); // recover client
        }
        $this->cookies = clone $this->cookies;
        $this->incremental_cookies = new Cookies();
    }

}
