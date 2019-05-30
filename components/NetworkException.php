<?php

namespace app\components;
/**
 * 网络请求异常
 */
class NetworkException extends \Exception
{
    public $url;
    public $method;
    public $params;
    public $header;
    public $content;
    public $statusCode;

    /** HTTP状态码 */
    const HTTP_CODE = array(
        100 => "HTTP 100 Continue",
        101 => "HTTP 101 Switching Protocols",
        200 => "HTTP 200 OK",
        201 => "HTTP 201 Created",
        202 => "HTTP 202 Accepted",
        203 => "HTTP 203 Non-Authoritative Information",
        204 => "HTTP 204 No Content",
        205 => "HTTP 205 Reset Content",
        206 => "HTTP 206 Partial Content",
        300 => "HTTP 300 Multiple Choices",
        301 => "HTTP 301 Moved Permanently",
        302 => "HTTP 302 Found",
        303 => "HTTP 303 See Other",
        304 => "HTTP 304 Not Modified",
        305 => "HTTP 305 Use Proxy",
        307 => "HTTP 307 Temporary Redirect",
        400 => "HTTP 400 Bad Request",
        401 => "HTTP 401 Unauthorized",
        402 => "HTTP 402 Payment Required",
        403 => "HTTP 403 Forbidden",
        404 => "HTTP 404 Not Found",
        405 => "HTTP 405 Method Not Allowed",
        406 => "HTTP 406 Not Acceptable",
        407 => "HTTP 407 Proxy Authentication Required",
        408 => "HTTP 408 Request Time-out",
        409 => "HTTP 409 Conflict",
        410 => "HTTP 410 Gone",
        411 => "HTTP 411 Length Required",
        412 => "HTTP 412 Precondition Failed",
        413 => "HTTP 413 Request Entity Too Large",
        414 => "HTTP 414 Request-URI Too Large",
        415 => "HTTP 415 Unsupported Media Type",
        416 => "HTTP 416 Requested range not satisfiable",
        417 => "HTTP 417 Expectation Failed",
        500 => "HTTP 500 Internal Server Error",
        501 => "HTTP 501 Not Implemented",
        502 => "HTTP 502 Bad Gateway",
        503 => "HTTP 503 Service Unavailable",
        504 => "HTTP 504 Gateway Time-out"
    );

    /**
     * NetworkException constructor.
     * @param int $errorCode
     * @param null $message
     * @param null $url
     * @param null $method
     * @param null $statusCode
     * @param null $params
     * @param null $header
     * @param null $content
     */
    public function __construct($errorCode = 0, $message = null, $url = null, $method = null, $statusCode = null, $params = null, $header = null, $content = null)
    {
        parent::__construct($message, $errorCode);

        $this->url = $url;
        $this->method = $method;
        $this->statusCode = $statusCode;
        $this->params = $params;
        $this->header = $header;
        $this->content = $content;
    }


    public function getName()
    {
        return 'network error';
    }
}