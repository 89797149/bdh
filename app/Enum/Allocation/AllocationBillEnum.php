<?php
/**
 * 调拨
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-26
 * Time: 19:15
 */

namespace App\Enum\Allocation;


class AllocationBillEnum
{
    const STATUS = array(//调拨状态【-1:平台已拒绝|0:平台待审核|1:调出方待确认|2:等待调入方收货|3:调出方已交货】
        -1 => '平台已拒绝',
        0 => '平台待审核',
        1 => '调出方待确认',
        2 => '等待调入方收货',
        3 => '调出方已交货',
    );

    static function getStatusName()
    {
        return self::STATUS;
    }
}