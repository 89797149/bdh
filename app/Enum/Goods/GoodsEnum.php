<?php

namespace App\Enum\Goods;

/**
 * 商品公共枚举
 * Class GoodsEnum
 */
class GoodsEnum
{
    //审核状态  0-未知 1-待审核 2-审核通过 3-审核不通过
    const VALIDATION_STATUS_UNKNOWN = 0;

    const PURCHASE_TYPE = array(
        0 => '',
        1 => '市场自采',
        2 => '供应商供货',
    );

    public function getValidationStatus()
    {
        return array(
            self::VALIDATION_STATUS_UNKNOWN => '未知',
        );
    }

    static function getPurchaseType()
    {
        return self::PURCHASE_TYPE;
    }
}