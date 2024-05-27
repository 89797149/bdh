<?php
/**
 * 入库
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-24
 * Time: 14:13
 */

namespace App\Enum\WarehousingBill;


class WarehousingBillEnum
{
    const WAREHOUSING_STATUS = array(//入库状态(0:未入库 1:已入库)
        '未入库',
        '已入库',
    );

    const BILL_TYPE = array(//单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
        1 => '采购入库',
        2 => '其他入库',
        3 => '调货入库',
        4 => '订单退货',
        5 => '单位转换',
        6 => '期初入库',
        7 => '报溢入库',
    );

    const BILL_INPUTSTATUS = array(//单据录入状态(0:未录入 1:部分录入 2:已录入)
        '未录入',
        '部分录入',
        '已录入',
    );

    const GOODS_INPUTSTATUS = array(//商品录入状态(0:未录入 1:已录入)
        '未录入',
        '已录入',
    );

    static function getWarehousingStatus()
    {
        return self::WAREHOUSING_STATUS;
    }

    static function getBillType()
    {
        return self::BILL_TYPE;
    }

    static function getBillInputStatus()
    {
        return self::BILL_INPUTSTATUS;
    }

    static function getGoodsInputStatus()
    {
        return self::GOODS_INPUTSTATUS;
    }
}