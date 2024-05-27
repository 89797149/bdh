<?php
/**
 * 结算单(线上)
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 12:00
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Models\OrdersModel;
use App\Modules\Banks\BanksModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Settlement\SettlementBillModule;
use App\Modules\Shops\ShopsModule;
use Think\Model;

class SettlementBillModel extends BaseModel
{
    /**
     * 结算单-结算单列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string billNo 结算单号
     * -int settlementStatus 结算状态(0:未结算 1:已结算) 不传默认全部
     * -date createDateStart 申请结算日期-开始日期
     * -date createDateEnd 申请结算日期-结束日期
     * -date settlementDateStart 结算日期区间-开始日期
     * -date settlementDateEnd 结算日期区间-结束日期
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getSettlementBillList(array $paramsInput)
    {
        $module = new SettlementBillModule();
        $result = $module->getSettlementBillList($paramsInput);
        return $result;

    }

    /**
     * 结算单-(未结算/已结算)订单列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string [billNo] 结算单号
     * -string [orderNo] 订单号
     * -string [customerName] 客户名称
     * -string [receivingName] 收货人姓名
     * -string [receivingPhone] 收货人手机号
     * -date [createOrderDateStart] 下单日期区间-开始日期
     * -date [createOrderDateEnd] 下单日期区间-结束日期
     * -int [settlementStatus] 结算状态(0:未结算 1:已结算) 不传默认全部
     * -date [applyDateStart] 申请结算日期区间-开始日期
     * -date [applyDateEnd] 申请结算日期区间-结束日期
     * -date [settlementDateStart] 结算日期区间-开始日期
     * -date [settlementDateEnd] 结算日期区间-结束日期
     * -date [page] 页码
     * -date [pageSize] 分页条数
     * @return array
     * */
    public function getSettlementOrderList(array $paramsInput)
    {
        $module = new SettlementBillModule();
        $result = $module->getSettlementOrderList($paramsInput);
        return $result;
    }

    /**
     * 结算单-创建结算单
     * @param array $loginInfo
     * @param array $orderIdArr 订单id
     * @return array
     * */
    public function addSettlementBill(array $loginInfo, array $orderIdArr)
    {
        $module = new SettlementBillModule();
        $orderModule = new OrdersModule();
        $shopId = $loginInfo['shopId'];
        $shopModule = new ShopsModule();
        $shopField = 'shopId,shopName,bankId,bankNo,bankUserName';
        $shopDetail = $shopModule->getShopsInfoById($shopId, $shopField, 2);
        if (empty($shopDetail['bankId']) || empty($shopDetail['bankNo']) || empty($shopDetail['bankUserName'])) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请补全店铺资料中的银行卡信息');
        }
        $bankDetail = (new BanksModule())->getBankDetialById($shopDetail['bankId']);
        if (empty($bankDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '门店绑定的银行卡信息异常');
        }
        $orderAmount = 0;//订单金额累积
        $orderDiffAmount = 0;//补差价金额累积
        $poundageAmount = 0;//佣金累积
        foreach ($orderIdArr as $orderId) {
            $orderDetail = $orderModule->getOrderInfoById($orderId, 'shopId,orderNo,orderStatus,settlementId,payType,realTotalMoney,poundageMoney', 2);
            if (empty($orderDetail)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单信息有误');
            }
            if ($orderDetail['shopId'] != $shopId) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单信息和门店不匹配');
            }
            if ($orderDetail['orderStatus'] != 4) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "订单#{$orderDetail['orderNo']}的状态不符合结算要求");
            }
            if ($orderDetail['settlementId'] != 0) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "订单#{$orderDetail['orderNo']}请勿重复结算");
            }
            if ($orderDetail['payType'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "订单#{$orderDetail['orderNo']}的支付方式不符合结算要求");
            }
            if ($orderDetail['realTotalMoney'] <= 0) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "订单#{$orderDetail['orderNo']}实付金额为0,无需结算");
            }
            $orderAmount += $orderDetail['realTotalMoney'];
            $poundageAmount += $orderDetail['poundageMoney'];
            $orderGoodsDiffList = $orderModule->getOrderGoodsDiffList($orderId, 1);
            $orderDiffAmount += array_sum(array_column($orderGoodsDiffList, 'money'));
        }
        $settlementAmount = bc_math($orderAmount, $poundageAmount, 'bcsub', 2);
        $settlementAmount = bc_math($settlementAmount, $orderDiffAmount, 'bcsub', 2);
        $settlementBillData = array(
            'shopId' => $shopId,
            'bankName' => $bankDetail['bankName'],
            'bankCardNo' => $shopDetail['bankNo'],
            'bankCardOwner' => $shopDetail['bankUserName'],
            'orderAmount' => $orderAmount,
            'orderDiffAmount' => $orderDiffAmount,
            'poundageAmount' => $poundageAmount,
            'settlementAmount' => $settlementAmount,
        );
        $trans = new Model();
        $trans->startTrans();
        $settlementId = $module->saveSettlementBill($settlementBillData, $trans);
        if (empty($settlementId)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '单据生成失败');
        }
        $saveOrderData = array(
            'settlementId' => $settlementId,
        );
        $orderWhere = array(
            'orderId' => array('in', $orderIdArr)
        );
        $orderModel = new OrdersModel();
        $saveOrderRes = $orderModel->where($orderWhere)->save($saveOrderData);
        if (!$saveOrderRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单信息更新失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_settlement_bill',
            'dataId' => $settlementId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'complainStatus',
            'fieldValue' => 0,
            'remark' => '创建结算单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '结算单创建失败', '日志记录失败');
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 结算单-结算单详情
     * @param int $settlementId 结算单id
     * @return array
     * */
    public function getSettlementBillDetail(int $settlementId, int $shopId)
    {
        $module = new SettlementBillModule();
        $result = $module->getSettlementBillDetail($settlementId);
        if (empty($result)) {
            return array();
        }
        if ($result['shopId'] != $shopId) {
            return $result;
        }
        $paramsInput = array(
            'shopId' => $shopId,
            'settlementId' => $settlementId,
            'usePage' => 0,
        );
        $orderList = $module->getSettlementOrderList($paramsInput);
        $result['order_list'] = $orderList;
        $result['log_data'] = (new TableActionLogModule())->getLogListByParams(array(
            'tableName' => 'wst_settlement_bill',
            'dataId' => $settlementId,
        ));
        return $result;
    }
}