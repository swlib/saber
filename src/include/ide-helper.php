<?php
/**
 * Author: Twosee <twose@qq.com>
 * Date: 2018/7/8 下午3:00
 */

namespace Swlib\Http\Exception {

    use RuntimeException;
    use Swlib\Saber\Request;
    use Swlib\Saber\Response;

    class TransferException extends RuntimeException
    {
    }

    /**
     * Class RequestException
     * @package Swlib\Http\Exception
     * @method Request getRequest()
     * @method Response|null getResponse()
     */
    class RequestException extends TransferException
    {
    }

    class BadResponseException extends RequestException
    {
    }

    class ClientException extends RequestException
    {
    }

    class ConnectException extends RequestException
    {
    }

    class ServerException extends RequestException
    {
    }

    class TooManyRedirectsException extends RequestException
    {
    }

}
