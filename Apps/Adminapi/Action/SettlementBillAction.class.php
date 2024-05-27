<?php
/**
 * 结算单(线上)
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-06
 * Time: 11:54
 */

namespace Adminapi\Action;


use Adminapi\Model\SettlementBillModel;

class SettlementBillAction extends BaseAction
{
    /**
     * 结算单-结算单列表
     * 文档链接地址:https://www.yuque.com/youzhibu/ruah6u/ai4sye
     * */
    public function getSettlementBillList()
    {
        $this->isLogin();
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => 0,
            'billNo' => '',
            'settlementStatus' => '',//结算状态(0:未结算 1:已结算) 不传默认全部
            'createDateStart' => '',//申请结算日期-开始日期
            'createDateEnd' => '',//申请结算日期-结束日期
            'settlementDateStart' => '',//结算日期区间-开始日期
            'settlementDateEnd' => '',//结算日期区间-结束日期
            'page' => 1,//页码
            'pageSize' => 15,//分页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new SettlementBillModel();
        $result = $mod->getSettlementBillList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }
}