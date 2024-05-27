<?php
/**
 * 采购单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-21
 * Time: 15:52
 */

namespace App\Enum\PurchaseBill;


class PurchaseBillEnum
{
    const PURCHASE_STATUS = array(//采购状态(-1:关闭 0:待采购 1:部分收货 2:全部收货)
        -1 => "关闭",
        0 => "待采购",
        1 => "部分收货",
        2 => "全部收货",
    );

    const SUPPLIER_CONFIRM = array(//供应商确认(0:未确认 1:已确认)
        "未确认",
        "已确认",
    );

    const BILL_TYPE = array(//单据类型(1:市场自采 2:供应商直供)
        1 => "市场自采",
        2 => "供应商直供",
    );

    const BILL_FROM = array(//单据来源单据来源(1:手动创建采购单 2:订单汇总生产采购单 3:预采购生成采购单 4:现场采购订单 5:采购任务生成采购单 6:采购任务生成采购单(联营))
        1 => "手动创建采购单",
        2 => "订单汇总生产采购单",
        3 => "预采购生成采购单",
        4 => "现场采购订单",
        5 => "采购任务生成采购单(联营))",
        6 => "采购任务生成采购单(联营)",
    );

    /**
     * 获取单据类型
     * @param int $code 状态码
     * @return string
     * */
    public function getBillType($code)
    {
        return self::BILL_TYPE[$code];
    }

    /**
     * 获取单据来源
     * @param int $code 状态码
     * @return string
     * */
    public function getBillFrom($code)
    {
        return self::BILL_FROM[$code];
    }

    /**
     * 获取采购状态名称
     * @param int $code 状态码
     * @return string
     * */
    public function getPurchaseStatus($ocde)
    {
        return self::PURCHASE_STATUS[$ocde];
    }

    /**
     * 获取供应商确认名称
     * @param int $code 状态码
     * @return string
     * */
    public function getSupplierConfirm($ocde)
    {
        return self::SUPPLIER_CONFIRM[$ocde];
    }

    const BILL_RETURN_STATUS = array(//退货单状态(-1:已关闭 0:待审核 1:已完成)
        -1 => "已关闭",
        0 => "待审核",
        1 => "已完成",
    );

    public function getBillReturnStatus(){
        return self::BILL_RETURN_STATUS;
    }

}