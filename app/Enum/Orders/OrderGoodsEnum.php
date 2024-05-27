<?php
/**
 * 订单商品
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-31
 * Time: 16:01
 */

namespace App\Enum\Orders;


class OrderGoodsEnum
{
    const PURCHASE_TYPE = array(//采购类型(1:市场自采 2:供应商供货)
        0 => '',
        1 => '市场自采',
        2 => '供应商供货',
    );

    static function getPurchaseType()
    {
        return self::PURCHASE_TYPE;
    }

}