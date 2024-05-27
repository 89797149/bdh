<?php
/**
 * 结算
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-06
 * Time: 10:02
 */

namespace App\Enum\Settlement;


class SettlementBillEnum
{
    const SETTLEMENT_STATUS = array(//结算状态(0:未结算 1:已结算)
        '未结算',
        '已结算',
    );

    static function getSettlementStatus()
    {
        return self::SETTLEMENT_STATUS;
    }
}