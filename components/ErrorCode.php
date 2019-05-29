<?php
namespace app\components;

/**
 * 错误代码管理
 */
class ErrorCode
{

    const ERR_SYTEM = [
        -1,
        '系统错误',
    ];

    const ERR_PARAM = [
        -2,
        '参数缺失',
    ];

    const ERR_EMPTY_RESULT = [
        -3,
        '请求接口无返回',
    ];

    const ERR_INVALID_PARAMETER = [
        -4,
        '请求参数错误',
    ];

    const ERR_CHECK_SIGN = [
        -5,
        '签名错误',
    ];

    const ERR_NO_PARAMETERS = [
        -6,
        '请求参数缺失',
    ];
}
