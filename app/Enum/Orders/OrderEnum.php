<?php
/**
 * 订单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-31
 * Time: 16:01
 */

namespace App\Enum\Orders;


class OrderEnum
{
    const PAY_FROM = array(//支付来源[1:支付宝，2：微信,3:余额,4:货到付款]
        1 => '支付宝',
        2 => '微信',
        3 => '余额',
        4 => '货到付款',
    );

    static function getPayFromName()
    {
        return self::PAY_FROM;
    }
}