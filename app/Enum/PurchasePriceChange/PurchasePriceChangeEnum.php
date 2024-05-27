<?php
/**
 * 成本调整
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-15
 * Time: 10:45
 */

namespace App\Enum\PurchasePriceChange;


class PurchasePriceChangeEnum
{
    const BILL_STATUS = array(//单据状态(0:待审核 1:已完成)
        '待审核',
        '已完成',
    );

    static function getBillStatus()
    {
        return self::BILL_STATUS;
    }
}