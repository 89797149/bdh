<?php
/**
 * 客户结算单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-17
 * Time: 10:02
 */

namespace App\Enum\Settlement;


class CustomerSettlementBillEnum
{
    const PAY_TYPE = array(//支付方式(1:支付宝，2：微信 3:余额  4:货到付款 5:现金 6:转账 7:线下支付宝 8:线下微信)
        1 => '支付宝',
        2 => '微信',
        3 => '余额',
        4 => '货到付款',
        5 => '现金',
        6 => '转账',
        7 => '线下支付宝',
        8 => '线下微信',
    );

    const BILL_STATUS = array(
        '未结算',
        '已完成',
    );

    const BILL_FROM = array(
        1 => '销售订单',
        2 => '退货单',
    );

    static function getPayType()
    {
        return self::PAY_TYPE;
    }

    static function getBillStatus()
    {
        return self::BILL_STATUS;
    }

    static function getBillFrom()
    {
        return self::BILL_FROM;
    }
}