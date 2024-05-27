<?php
/**
 * 结算单(线上)
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 11:59
 */

namespace Merchantapi\Action;


use App\Enum\ExceptionCodeEnum;
use Merchantapi\Model\SettlementBillModel;

class SettlementBillAction extends BaseAction
{
    /**
     * 结算单-(未结算/已结算)订单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/cr0ady
     * */
    public function getSettlementOrderList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
            'billNo' => '',//结算单号
            'orderNo' => '',//订单号
            'customerName' => '',//客户名称
            'receivingName' => '',//收货人姓名
            'receivingPhone' => '',//收货人手机号
            'createOrderDateStart' => '',//下单日期区间-开始日期
            'createOrderDateEnd' => '',//下单日期区间-结束日期
            'settlementStatus' => '',//结算状态(0:未结算 1:已结算) 不传默认全部
            'applyDateStart' => '',//申请结算日期区间-开始日期
            'applyDateEnd' => '',//申请结算日期区间-结束日期
            'settlementDateStart' => '',//结算日期区间-开始日期
            'settlementDateEnd' => '',//结算日期区间-结束日期
            'page' => 1,//页码
            'pageSize' => 15,//分页条数
        );
        parm_filter($paramsInput, $paramsReq);
        $mod = new SettlementBillModel();
        $result = $mod->getSettlementOrderList($paramsInput);
        $this->ajaxReturn(returnData($result));
    }

    /**
     * 结算单-创建结算单
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/pb9bgp
     * */
    public function addSettlementBill()
    {
        $loginInfo = $this->MemberVeri();
        $orderIdArr = json_decode(htmlspecialchars_decode(I('orderIdArr')), true);
        if (empty($orderIdArr)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要结算的订单'));
        }
        $mod = new SettlementBillModel();
        $result = $mod->addSettlementBill($loginInfo, $orderIdArr);
        $this->ajaxReturn($result);
    }

    /**
     * 结算单-结算单列表
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/zyk5kl
     * */
    public function getSettlementBillList()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $paramsReq = I();
        $paramsInput = array(
            'shopId' => $shopId,
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

    /**
     * 结算单-结算单详情
     * 文档链接地址:https://www.yuque.com/anthony-6br1r/oq7p0p/veit2k
     * */
    public function getSettlementBillDetail()
    {
        $loginInfo = $this->MemberVeri();
        $shopId = $loginInfo['shopId'];
        $settlementId = (int)I('settlementId');
        if (empty($settlementId)) {
            $this->ajaxReturn(returnData(false, ExceptionCodeEnum::FAIL, 'error', '传参有误-缺少settlementId'));
        }
        $mod = new SettlementBillModel();
        $result = $mod->getSettlementBillDetail($settlementId, $shopId);
        $this->ajaxReturn(returnData($result));
    }
}