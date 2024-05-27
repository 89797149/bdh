<?php

namespace App\Util;

use \CjsProtocol\ApiResponse;

function response($code, $msg = '', $data = '', $method = '')
{
    if (!$method) {
        $method = __METHOD__;
    }
    $obj = ApiResponse::getInstance()->setCode($code)->setMsg($msg)->setData($data);
    //待补日志 todo

    return $obj->toArray();
}

/**
 * 请求成功
 * @param null $data
 * @param string $method
 * @return array
 */
function responseSuccess($data = null, $msg = '成功', $method = '')
{
    return response(0, $msg, $data, $method);
}

/**
 * 异常错误返回
 * @param $code
 * @param string $msg
 * @param null $data
 * @param string $method
 * @return array
 */
function responseError($code, $msg = '失败', $data = null, $method = '')
{
    if ($code == '0') {
        $code = '-1';
        $msg = '系统异常';
    }
    return response($code, $msg, $data, $method);
}


/**
 * 将人民币单位分转化为元
 * @param $param
 * @param int $scale 保留小数位
 * @param string $default
 * @return string
 */
function fen2yuan($param, $scale = 2, $default = '0.00')
{
    if (empty($param)) {
        return $default;
    }
    $res = bcdiv($param, 100, $scale);
    return strval($res);
}

/**
 * @param $uid 用户ID，没有传0
 * @param $scene 场景代号 或 表名
 * @param string $orderPrefix 生成的代号前缀，默认so
 * @return string
 */
function generateSeq($uid, $scene, $orderPrefix = 'so')
{
    \CjsRedis\Sequence::setTblPrifix($scene, $orderPrefix);
    $seq = \CjsRedis\Sequence::getNextGlobalId($scene, $uid);
    return $seq;
}
