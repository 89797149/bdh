<?php
/**
 * 库房-出库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-07
 * Time: 13:53
 */

namespace App\Enum\ExWarehouse;


class ExWarehouseOrderEnum
{
    const PAGE_TYPE = array(//出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
        1 => "销售出库",
        2 => "其他出库",
        3 => "调货出库",
        4 => "采购退货",
        5 => "单位转换",
        6 => "报损或盘损出库",
    );
    const EXAMINE_STATUS = array(//审核状态[1:未审核出库|2:已审核出库|3：拒绝审核]
        1 => "待出库",
        2 => "已出库",
        3 => "出库失败",
    );
}