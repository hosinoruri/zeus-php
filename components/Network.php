<?php

namespace app\components;

/**
 * 网络请求
 */
class Network
{

    /**
     * 请求结果为json并格式化为数组
     */
    const FORMAT_ARRAY_JSON = 'array';

    /**
     * 请求结果为json并格式化为对象
     */
    const FORMAT_OBJECT_JSON = 'object';

    /**
     * 请求结果为字符串
     */
    const FORMAT_STRING = 'string';

    /**
     * 超时时间
     * @var int
     */
    private $timeout = 30;

    /**
     * 是否需要处理错误，只针对结果为FORMAT_ARRAY_JSON或FORMAT_OBJECT_JSON有效
     * @var bool
     */
    private $isHandleError = false;

    /**
     * 格式化结果
     * @var string
     */
    private $format = self::FORMAT_ARRAY_JSON;

    public function __construct(array $config = array())
    {
        foreach ($config as $param => $value) {
            $this->$param = $value;
        }
    }

    /**
     * 批量get请求
     * @param array $requests 请求对象
     * array(
     *   array(
     *     'url' => '请求地址',
     *     'params' => array('a' => 'a'),// 或a=a&b=b；不填默认为空数组
     *     'header' => array(),// 不填默认为空数组
     *     'callback' => mixed,// 符合 @see call_user_func() 的回调函数；不填则不回调
     *   )
     * )
     */
    public function multiGet(array $requests)
    {
        $mh = curl_multi_init();
        $keyScores = array();

        // 添加curl，批处理会话
        foreach ($requests as $key => $request) {
            list($url, $params, $header) = $this->parseMultiRequest($request);
            $url = $this->buildQueryString($url, $params);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);// 不取返回的头部信息
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);// 非https
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);// 设置请求头
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);// 超时时间

            $request['curl'] = $ch;
            $requests[$key] = $request;
            $keyScores[intval($ch)] = $key;

            curl_multi_add_handle($mh, $ch);
        }

        $this->multiExec($mh, $requests, $keyScores);
    }

    /**
     * 批量post请求
     * @param array $requests 请求对象
     * array(
     *   array(
     *     'url' => '请求地址',
     *     'params' => array('a' => 'a'),// 或a=a&b=b；不填默认为空数组
     *     'header' => array(),// 不填默认为空数组
     *     'callback' => mixed,// 符合 @see call_user_func() 的回调函数；不填则不回调
     *   )
     * )
     */
    public function multiPost(array $requests)
    {
        $mh = curl_multi_init();
        $keyScores = array();

        // 添加curl
        foreach ($requests as $key => $request) {
            list($url, $params, $header) = $this->parseMultiRequest($request);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            $request['curl'] = $ch;
            $requests[$key] = $request;
            $keyScores[intval($ch)] = $key;

            curl_multi_add_handle($mh, $ch);
        }

        $this->multiExec($mh, $requests, $keyScores);
    }

    /**
     * 解析批量请求对象
     * @param $request
     * @return array
     */
    private function parseMultiRequest($request)
    {
        $params = isset($request['params']) ? $request['params'] : array();
        $header = isset($request['header']) ? $request['header'] : array();
        $url = isset($request['url']) ? $request['url'] : null;

        return [$url, $params, $header];
    }

    /**
     * 批量执行
     * @param $mh
     * @param array $requests
     * @param $keyScores
     */
    private function multiExec($mh, array $requests, $keyScores)
    {
        // 执行处理
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active and $mrc == CURLM_OK) {
            if(curl_multi_select($mh) === -1){
                usleep(100);
            }

            do {
                $mrc = curl_multi_exec($mh, $active);
            } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }

        // 获取结果，并回调
        while($done = curl_multi_info_read($mh)) {
            $key = isset($keyScores[intval($done['handle'])]) ? $keyScores[intval($done['handle'])] : null;
            if (isset($requests[$key]) && isset($requests[$key]['callback'])) {
                $request = $requests[$key];
                $ch = $request['curl'];
                $content = curl_multi_getcontent($ch);
                call_user_func($request['callback'], $key, $done['result'], $content, $ch);
            }
        }

        // 移除句柄
        foreach ($requests as $request) {
            curl_multi_remove_handle($mh, $request['curl']);
        }

        curl_multi_close($mh);
    }

    /**
     * Get请求
     * @param $url
     * @param array $params
     * @param array $httpHeader
     * @return mixed
     * @throws NetworkException
     */
    public function get($url, array $params = array(), array $httpHeader = array())
    {
        $url = $this->buildQueryString($url, $params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);// 不取返回的头部信息
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);// 非https
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);// 设置请求头
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);// 超时时间
        $networkStr = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = $curlInfo['http_code'];
        curl_close($ch);

        if ($errno) {
            throw new NetworkException(ErrorCode::ERR_EMPTY_RESULT[0], $error, $url, 'GET', 0, $params, $httpHeader);
        } elseif ($httpCode == 200) {
            return $this->formatResult($networkStr);
        } else {
            $error = NetworkException::HTTP_CODE[$httpCode] ? NetworkException::HTTP_CODE[$httpCode] :  "HTTP {$httpCode}";

            throw new NetworkException(ErrorCode::ERR_EMPTY_RESULT[0], $error, $url, 'GET', $httpCode, $params, $httpHeader);
        }
    }

    /**
     * Post请求
     * @param $url
     * @param array|string $params
     * @param array $httpHeader
     * @return mixed
     * @throws NetworkException
     */
    public function post($url, $params = array(), array $httpHeader = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);

        $networkStr = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = $curlInfo['http_code'];
        curl_close($ch);

        if ($errno) {
            throw new NetworkException(ErrorCode::ERR_EMPTY_RESULT[0], $error, $url, 'GET', 0, $params, $httpHeader);
        } elseif ($httpCode == 200) {
            return $this->formatResult($networkStr);
        } else {
            $error = NetworkException::HTTP_CODE[$httpCode] ? NetworkException::HTTP_CODE[$httpCode] : "HTTP {$httpCode}";

            throw new NetworkException(ErrorCode::ERR_EMPTY_RESULT[0], $error, $url, 'GET', $httpCode, $params, $httpHeader);
        }
    }

    /**
     * 生成查询字串
     * @param $url
     * @param $params
     * @return string
     */
    private function buildQueryString($url, $params)
    {
        // 分离baseUrl与查询字串
        @list($baseUrl, $queryStr) = explode('?', $url);
        $params = http_build_query($params);

        if (empty($params) && empty($queryStr)) {
            return $baseUrl;
        } elseif (empty($params)) {
            return "{$baseUrl}?{$queryStr}";
        } elseif (empty($queryStr)) {
            return "{$baseUrl}?{$params}";
        } else {
            return "{$baseUrl}?{$queryStr}&{$params}";
        }
    }

    /**
     * 格式化请求结果
     * @param $result
     * @return array|object|string
     * @throws NetworkException
     */
    private function formatResult($result)
    {
        switch ($this->format) {
            // 返回数组
            case self::FORMAT_ARRAY_JSON:

            // 返回对象
            case self::FORMAT_OBJECT_JSON:
                $assoc = $this->format == self::FORMAT_ARRAY_JSON;
                $source = $result;
                $result = json_decode($result, $assoc);
                try {
                    $this->checkJsonLastError();
                } catch (NetworkException $e) {
                    throw new NetworkException(ErrorCode::ERR_EMPTY_RESULT[0], "{$e->getMessage()}\n{$source}");
                }

                // 是否需要处理错误
                if ($this->isHandleError) {
                    return $this->handleError($result);
                } else {
                    return $result;
                }

            // 返回字符串
            case self::FORMAT_STRING:
            default:
                return $result;
        }
    }

    /**
     * 检查json解析是否有错误
     * @throws NetworkException
     */
    private function checkJsonLastError()
    {
        static $jsonErrors = array(
            JSON_ERROR_DEPTH => 'JSON_ERROR_DEPTH - Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'JSON_ERROR_STATE_MISMATCH - Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'JSON_ERROR_CTRL_CHAR - Unexpected control character found',
            JSON_ERROR_SYNTAX => 'JSON_ERROR_SYNTAX - Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'JSON_ERROR_UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded'
        );

        if (JSON_ERROR_NONE !== json_last_error()) {
            $last = json_last_error();
            $msg = isset($jsonErrors[$last]) ? $jsonErrors[$last] : 'Unknown error';

            throw new NetworkException(0, "数据格式错误：{$msg}");
        }
    }

    /**
     * 处理错误
     * @param $result
     * @return mixed|string
     * @throws NetworkException
     */
    private function handleError($result)
    {
        $code = ArrayHelper::getValue($result, 'code');

        if ($code == 0) {
            return ArrayHelper::getValue($result, 'data');
        } else {
            $msg = ArrayHelper::getValue($result, 'msg');

            throw new NetworkException($code, $msg);
        }
    }
}