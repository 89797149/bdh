<?php
/**
 * 客户结算单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-16
 * Time: 14:40
 */

namespace App\Modules\Settlement;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Orders\OrderEnum;
use App\Enum\Settlement\CustomerSettlementBillEnum;
use App\Models\CustomerSettlementBillModel;
use App\Models\OrdersModel;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\PSD\DriverModule;
use App\Modules\PSD\LineModule;
use App\Modules\Users\UsersModule;
use Think\Model;

class CustomerSettlementBillModule
{
    /**
     * 客户结算-创建客户结算单
     * @param array $paramsInput 参数
     * -array loginInfo 登陆者信息
     * --user_id 登陆者id
     * --user_username 登陆者姓名
     * -int relation_order_id 关联单据id
     * -int billFrom 单据来源(1:销售订单 2:退货单)
     * -int ignoreType 是否是货到付款订单(0:否 1:是)
     * @param object $trans
     * @return array
     * */
    public function createCustomerSerrlementBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $loginInfo = $paramsInput['loginInfo'];
        if (empty($loginInfo)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败-loginInfo参数异常');
        }
        $businessParams = array(
            'relation_order_id' => 0,
            'billFrom' => 0,
            'ignoreType' => 0,
            'settlementRemark' => '',
            'realAmount' => 0,
            'zeroAmount' => 0,
            'payType' => 0,
            'billPic' => '',
            'billRemark' => '',
            'payerName' => '',
        );
        parm_filter($businessParams, $paramsInput);
        $relation_order_id = $businessParams['relation_order_id'];
        $billFrom = $businessParams['billFrom'];
        $ignoreType = $businessParams['ignoreType'];
        if (empty($relation_order_id) || empty($billFrom)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败-关联单据传参有误');
        }
//        $usersModule = new UsersModule();
        $ordersModule = new OrdersModule();
        if ($billFrom == 1) {//销售订单

            $orderRow = $ordersModule->getOrderInfoById($relation_order_id, '*', 2);
            if (empty($orderRow)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败-关联单据信息有误');
            }
            if ($orderRow['payFrom'] == 4 && $ignoreType == 0) {//货到付款直接跳过
                if (empty($trans)) {
                    $dbTrans->commit();
                }
                return returnData(true);
            }
            if (!empty($orderRow['customerSettlementId'])) {
//                $dbTrans->rollback();
//                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已结算的单据不能重复结算');
                if (empty($trans)) {
                    $dbTrans->commit();
                }
                return returnData(true);
            }
//            $userId = $orderRow['userId'];
//            $usersRow = $usersModule->getUsersDetailById($userId, 'userId,userName', 2);
            $billParams = array(
                'customerId' => $orderRow['userId'],
                'creatorId' => $loginInfo['user_id'],
                'shopId' => $orderRow['shopId'],
                'creatorName' => $loginInfo['user_username'],
                'billStatus' => 1,
                'billFrom' => 1,
                'orderId' => $orderRow['orderId'],
                'relation_order_id' => $orderRow['orderId'],
                'relation_order_number' => $orderRow['orderNo'],
//                'payerId' => $orderRow['userId'],
                'payerName' => $orderRow['payment_username'],
                'payType' => $orderRow['payFrom'],
                'billAmount' => $orderRow['realTotalMoney'],
                'receivableAmount' => $orderRow['realTotalMoney'],
                'zeroAmount' => 0,
                'billRemark' => '在线支付自动结算',
                'billPic' => '',
            );
            if ($ignoreType == 1) {
                $billParams['billAmount'] = $businessParams['realAmount'];
                $billParams['receivableAmount'] = $businessParams['realAmount'];
                $billParams['billRemark'] = $businessParams['settlementRemark'];
                $billParams['payType'] = $businessParams['payType'];
                $billParams['billPic'] = $businessParams['billPic'];
                $billParams['payerName'] = $businessParams['payerName'];
                if (empty($billParams['billRemark'])) {
                    $billParams['billRemark'] = '货到付款结算';
                }
            }
            $settlementId = $this->saveCustomerSettlementBill($billParams, $dbTrans);
            if (empty($settlementId)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败');
            }
            $orderParams = array(
                'orderId' => $orderRow['orderId'],
                'customerSettlementId' => $settlementId,
                'customerSettlementDate' => date('Y-m-d H:i:s'),
            );
            if ($ignoreType == 1) {
                $orderParams['customerSettlementPic'] = $businessParams['billPic'];
                $orderParams['customerSettlementRemark'] = $businessParams['billRemark'];
            }
            $saveOrderRes = $ordersModule->saveOrdersDetail($orderParams, $dbTrans);
            if (!$saveOrderRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败-订单更新失败');
            }
        }
        if ($billFrom == 2) {//退货单
            $complainDetail = $ordersModule->getComplainsDetailById($relation_order_id);
            $orderId = $complainDetail['orderId'];
            $ordersField = 'orderId,orderNo,orderNo,userId,realTotalMoney,customerSettlementId,payFrom,shopId,returnAmount';
            $orderRow = $ordersModule->getOrderInfoById($orderId, $ordersField, 2);
            if (empty($orderRow)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败-关联单据信息有误');
            }
            $billParams = array(
                'customerId' => $orderRow['userId'],
                'creatorId' => $loginInfo['user_id'],
                'shopId' => $orderRow['shopId'],
                'creatorName' => $loginInfo['user_username'],
                'billStatus' => 1,
                'billFrom' => 2,
                'orderId' => $orderRow['orderId'],
                'relation_order_id' => $relation_order_id,
                'relation_order_number' => $orderRow['orderNo'],
                'payerId' => $loginInfo['user_id'],
                'payerName' => $loginInfo['user_username'],
                'payType' => $orderRow['payFrom'],
                'billAmount' => -$complainDetail['returnAmount'],
                'receivableAmount' => -$complainDetail['returnAmount'],
                'zeroAmount' => 0,
                'billRemark' => '退货确定退款自动结算',
                'billPic' => $complainDetail['complainAnnex'],
            );
            if ($ignoreType == 1) {
                $billParams['billAmount'] = $businessParams['realAmount'];
                $billParams['receivableAmount'] = $businessParams['realAmount'];
                $billParams['billRemark'] = $businessParams['settlementRemark'];
                $billParams['payType'] = $businessParams['payType'];
                $billParams['billPic'] = $businessParams['billPic'];
                $billParams['payerName'] = $businessParams['payerName'];
                $complainsParams = array(
                    'complainId' => $complainDetail['complainId'],
                    'complainStatus' => 2,
                    'respondTime' => date("Y-m-d H:i:s"),
                    'returnAmountStatus' => 1,
                    'returnAmount' => abs($billParams['billAmount']),
                );
                $saveComplainsRes = $ordersModule->saveOrderGoodsComplains($complainsParams, $dbTrans);
                if (!$saveComplainsRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败', '退货单更新失败');
                }
                $recordParams = array();
                $recordParams['orderId'] = $orderId;
                $recordParams['goodsId'] = $complainDetail['goodsId'];
                $recordParams['money'] = $billParams['billAmount'];
                $recordParams['addTime'] = date('Y-m-d H:i:s');
                $recordParams['payType'] = $orderRow['payFrom'];
                $recordParams['userId'] = $orderRow['userId'];
                $recordParams['skuId'] = $complainDetail['skuId'];
                $recordParams['complainId'] = $complainDetail['complainId'];
                $saveComplainsRecordRes = $ordersModule->saveOrderGoodsComplainsrecord($recordParams, $dbTrans);
                if (!$saveComplainsRecordRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败', '售后记录失败');
                }
            }
            $settlementId = $this->saveCustomerSettlementBill($billParams, $dbTrans);
            if (empty($settlementId)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败');
            }
            $orderParams = array(
                'orderId' => $orderRow['orderId'],
                'returnStatus' => 1,
                'returnAmount' => $complainDetail['returnAmount'],
            );
            if ($ignoreType == 1) {
                $orderParams['returnAmount'] = $businessParams['realAmount'];
                $orderParams['customerSettlementPic'] = $businessParams['billPic'];
                $orderParams['customerSettlementRemark'] = $businessParams['billRemark'];
            }
            $orderParams['returnAmount'] = bc_math($orderParams['returnAmount'], $orderRow['returnAmount'], 'bcadd', 2);
            $saveOrderRes = $ordersModule->saveOrdersDetail($orderParams, $dbTrans);
            if (!$saveOrderRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '客户结算单创建失败-订单更新失败');
            }
        }
        $tableActionModule = new TableActionLogModule();
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_customer_settlement_bill',
            'dataId' => $settlementId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => 0,
            'remark' => '创建结算单',
        ];
        $logRes = $tableActionModule->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "日志记录失败");
        }
        if ($billParams['billStatus'] == 1) {
            $logParams = [//写入状态变动记录表
                'tableName' => 'wst_customer_settlement_bill',
                'dataId' => $settlementId,
                'actionUserId' => $loginInfo['user_id'],
                'actionUserName' => $loginInfo['user_username'],
                'fieldName' => 'billStatus',
                'fieldValue' => 1,
                'remark' => '结算单已结算',
            ];
            $logRes = $tableActionModule->addTableActionLog($logParams, $dbTrans);
            if (!$logRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "日志记录失败");
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 客户结算单-单据信息-保存
     * @param array $paramsInput
     * -wst_customer_settlement_bill 表字段
     * @param object $trans
     * @return int
     * */
    public function saveCustomerSettlementBill(array $paramsInput, $trans)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new CustomerSettlementBillModel();
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'settlementNo' => '',
            'shopId' => '',
            'customerId' => '',
            'billAmount' => '',
            'receivableAmount' => '',
            'zeroAmount' => '',
            'billFrom' => '',
            'relation_order_id' => '',
            'orderId' => '',
            'relation_order_number' => '',
            'payerName' => '',
            'payType' => '',
            'billStatus' => '',
            'billRemark' => '',
            'billPic' => '',
            'creatorId' => '',
            'creatorName' => '',
            'isDelete' => '',
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = $datetime;
            }
        }
        if (empty($paramsInput['settlementId'])) {
            $saveParams['createTime'] = $datetime;
            $settlementId = $model->add($saveParams);
            if (!$settlementId) {
                $dbTrans->rollback();
                return 0;
            }
            $where = array(
                'settlementId' => $settlementId
            );
            $saveParams = array(
                'settlementId' => $settlementId,
                'settlementNo' => 'SE' . date('Ymd') . str_pad($settlementId, 10, "0", STR_PAD_LEFT),
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $settlementId = $paramsInput['settlementId'];
            $where = array(
                'settlementId' => $settlementId
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$settlementId;
    }

    /**
     * 结算单-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int payType 支付方式(1:支付宝，2：微信 3:余额  4:货到付款 5:现金 6:转账 7:线下支付宝 8:线下微信)
     * -date createDateStart 单据创建日期区间-开始日期
     * -date createDateEnd 单据创建日期区间-结束日期
     * -string customerKeywords 客户信息(客户名称/联系人/手机号)
     * -string settlementNo 结算单号
     * -string relation_order_number 业务单号
     * -int page 页码
     * -int pageSize 页码
     * */
    public function getCustomerSettlementBillList(array $paramsInput)
    {
        $searchParams = array(
            'shopId' => '',
            'payType' => '',
            'createDateStart' => '',
            'createDateEnd' => '',
            'customerKeywords' => '',
            'settlementNo' => '',
            'relation_order_number' => '',
            'page' => 1,
            'pageSize' => 15,
        );
        parm_filter($searchParams, $paramsInput);
        $model = new CustomerSettlementBillModel();
        $where = "settlement.isDelete=0 ";
        if (!empty($searchParams['shopId'])) {
            $where .= " and settlement.shopId={$searchParams['shopId']} ";
        }
        if (!empty($searchParams['payType'])) {
            $where .= " and settlement.payType={$searchParams['payType']} ";
        }
        if (!empty($searchParams['createDateStart'])) {
            $where .= " and settlement.createTime >= '{$searchParams['createDateStart']} 00:00:00' ";
        }
        if (!empty($searchParams['createDateEnd'])) {
            $where .= " and settlement.createTime <= '{$searchParams['createDateEnd']} 23:59:59' ";
        }
        if (!empty($searchParams['customerKeywords'])) {
            $where .= " and (users.userName like '%{$searchParams['customerKeywords']}%' or users.userPhone like '%{$searchParams['customerKeywords']}%') ";
        }
        if (!empty($searchParams['settlementNo'])) {
            $where .= " and settlement.settlementNo like '%{$searchParams['settlementNo']}%' ";
        }
        if (!empty($searchParams['relation_order_number'])) {
            $where .= " and settlement.relation_order_number like '%{$searchParams['relation_order_number']}%' ";
        }
        $field = 'settlement.settlementId,settlement.settlementNo,settlement.billAmount,settlement.receivableAmount,settlement.zeroAmount,settlement.payType,settlement.billStatus,settlement.createTime,settlement.creatorName,settlement.relation_order_number';
        $field .= ',users.userName as customerName,users.userPhone as customerPhone';
        $sql = $model
            ->alias('settlement')
            ->join('left join wst_users users on users.userId=settlement.customerId')
            ->where($where)
            ->field($field)
            ->order('settlement.createTime desc')
            ->buildSql();
        $result = $model->pageQuery($sql, $searchParams['page'], $searchParams['pageSize']);
        $billAmountTotalCurrPage = 0;
        foreach ($result['root'] as &$item) {
            $item['customerName'] = (string)$item['customerName'];
            $item['customerPhone'] = (string)$item['customerPhone'];
            $item['payTypeName'] = CustomerSettlementBillEnum::getPayType()[$item['payType']];
            $billAmountTotalCurrPage += $item['billAmount'];
        }
        unset($item);
        $result['billAmountTotalCurrPage'] = $billAmountTotalCurrPage;
        return $result;
    }

    /**
     * 客户结算单-详情-结算单id查找
     * @param int $settlementId 结算单id
     * @return array
     * */
    public function getCustomerSettlementBillDetailById(int $settlementId)
    {
        $model = new CustomerSettlementBillModel();
        $where = array(
            'settlement.settlementId' => $settlementId,
            'settlement.isDelete' => 0,
        );
        $field = 'settlement.settlementId,settlement.shopId,settlement.settlementNo,settlement.billAmount,settlement.receivableAmount,settlement.zeroAmount,settlement.billFrom,settlement.payType,settlement.billStatus,settlement.createTime,settlement.creatorName,settlement.relation_order_id,settlement.relation_order_number,settlement.payerName,settlement.billRemark,settlement.billPic';
        $field .= ',users.userName as customerName,users.userPhone as customerPhone';
        $result = $model
            ->alias('settlement')
            ->join('left join wst_users users on users.userId=settlement.customerId')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($result)) {
            return array();
        }
        $result['customerName'] = (string)$result['customerName'];
        $result['customerPhone'] = (string)$result['customerPhone'];
        $result['payTypeName'] = CustomerSettlementBillEnum::getPayType()[$result['payType']];
        $result['billStatusName'] = CustomerSettlementBillEnum::getBillStatus()[$result['billStatus']];;
        $result['billPic'] = explode(',', $result['billPic']);
        return $result;
    }

    /**
     * 客户结算单-关联单据
     * @param int $settlementId 结算单id
     * @return array
     * */
    public function getRelationBillData(int $settlementId)
    {
        $settlementDetail = self::getCustomerSettlementBillDetailById($settlementId);
        if (empty($settlementDetail)) {
            return array();
        }
        $billFrom = $settlementDetail['billFrom'];//单据来源(1:销售订单 2:退货单)
        $relation_order_id = $settlementDetail['relation_order_id'];//关联单据id
        $ordersModule = new OrdersModule();
        if ($billFrom == 1) {
            $ordersData = $ordersModule->getOrderInfoById($relation_order_id);
            if ($ordersData['code'] != ExceptionCodeEnum::SUCCESS) {
                return array();
            }
            $orderRow = $ordersData['data'];
            $returnData = array(
                'keyNum' => 1,
                'originalNo' => $orderRow['orderNo'],//原始单号
                'businessTypeName' => '销售订单',//业务类型
                'receivableAmount' => $orderRow['receivableAmount'],//应收金额
                'receivedAmount' => $orderRow['receivedAmount'],//已收金额
                'paidInAmount' => $orderRow['paidInAmount'],//实收金额
                'zeroAmount' => $orderRow['zeroAmount'],//抹零金额
                'uncollectedAmount' => $orderRow['uncollectedAmount'],//未收金额
                'dueDate' => $orderRow['pay_time'],//应收日期
                'billRemark' => '在线支付自动结算',//备注
            );
        }
        if ($billFrom == 2) {
            $complainsDetail = $ordersModule->getComplainsDetailById($relation_order_id);
            $orderId = $complainsDetail['orderId'];
            $ordersData = $ordersModule->getOrderInfoById($orderId);
            if ($ordersData['code'] != ExceptionCodeEnum::SUCCESS) {
                return array();
            }
            $orderRow = $ordersData['data'];
            $returnData = array(
                'keyNum' => 1,
                'originalNo' => $orderRow['orderNo'],//原始单号
                'businessTypeName' => '订单退货',//业务类型
                'receivableAmount' => formatAmountNum(-$complainsDetail['returnAmount']),//应收金额
                'receivedAmount' => formatAmountNum(-$complainsDetail['returnAmount']),//已收金额
                'paidInAmount' => formatAmountNum(-$complainsDetail['returnAmount']),//实收金额
                'zeroAmount' => formatAmountNum(0),//抹零金额
                'uncollectedAmount' => formatAmountNum(0),//未收金额
                'dueDate' => $complainsDetail['respondTime'],//应收日期
                'billRemark' => '退货确定退款自动结算',//备注
            );
        }
        //目前没有发现关联多个单据的场景,先做一个吧
        return array($returnData);
    }

    /**
     * 客户结算-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -date requireDateStart 要求送达日期区间-开始日期
     * -date requireDateEnd 要求送达日期区间-结束日期
     * -string customerKeywords 关键字搜索(客户名称/手机号/订单号)
     * -date customerSettlementDateStart 结算日期区间-开始日期
     * -date customerSettlementDateEnd 结算日期区间-结束日期
     * -date payTimeStart 支付日期区间-开始日期
     * -date payTimeEnd 支付日期区间-结束日期
     * -int customerSettlementStatus 客户结算状态(0:未结算 1:已结算) 不传默认全部
     * -int deliveryStatus 发货状态(0:未发货 1:已发货) 不传默认全部
     * -int returnStatus 退货状态(0:无退货 1:有退货) 不传默认全部
     * -int lineId 线路id
     * -int driverId 司机id
     * -int rankId 客户类型id
     * -array payFrom 支付方式(1:支付宝，2：微信,3:余额,4:货到付款)
     * -int usePage 是否使用分页(0:否 1:是)
     * -int page 页码
     * -int pageSize 每页条数
     * @return array
     * */
    public function getCustomerSettlementList(array $paramsInput)
    {
        $searchWhere = array(
            'shopId' => 0,
            'requireDateStart' => '',//要求送达日期区间-开始日期
            'requireDateEnd' => '',//要求送达日期区间-结束日期
            'customerKeywords' => '',//关键字搜索(客户名称/手机号/订单号)
            'customerSettlementDateStart' => '',//结算日期区间-开始日期
            'customerSettlementDateEnd' => '',//结算日期区间-结束日期
            'payTimeStart' => '',//支付日期区间-开始日期
            'payTimeEnd' => '',//支付日期区间-结束日期
            'customerSettlementStatus' => '',//客户结算状态(0:未结算 1:已结算) 不传默认全部
            'deliveryStatus' => '',//发货状态(0:未发货 1:已发货) 不传默认全部
            'returnStatus' => '',//退货状态(0:无退货 1:有退货) 不传默认全部
            'lineId' => '',//线路id
            'driverId' => '',//司机id
            'rankId' => '',//客户类型id
            'payFrom' => array(),//支付方式(1:支付宝，2：微信,3:余额,4:货到付款)
            'usePage' => 1,//是否使用分页(0:否 1:是)
            'page' => 1,//页码
            'pageSize' => 15,//分页条数
        );
        parm_filter($searchWhere, $paramsInput);
        $ordersModel = new OrdersModel();
        $where = 'orders.orderFlag=1 and orders.orderStatus IN(2,3,4,7,8,16,17) ';
        if (!empty($searchWhere['shopId'])) {
            $where .= " and orders.shopId={$searchWhere['shopId']} ";
        }
        if (!empty($searchWhere['requireDateStart'])) {
            $where .= " and orders.requireTime>='{$searchWhere['requireDateStart']} 00:00:00' ";
        }
        if (!empty($searchWhere['requireDateEnd'])) {
            $where .= " and orders.requireTime<='{$searchWhere['requireDateEnd']} 23:59:59' ";
        }
        if (!empty($searchWhere['customerKeywords'])) {
            $where .= " and (orders.orderNo like '%{$searchWhere['customerKeywords']}%' or orders.userName like '%{$searchWhere['customerKeywords']}%' or orders.userPhone like '%{$searchWhere['customerKeywords']}%' or users.userName like '%{$searchWhere['customerKeywords']}%') ";
        }
        if (!empty($searchWhere['customerSettlementDateStart'])) {
            $where .= " and orders.customerSettlementDate>='{$searchWhere['customerSettlementDateStart']} 00:00:00' ";
        }
        if (!empty($searchWhere['customerSettlementDateEnd'])) {
            $where .= " and orders.customerSettlementDate<='{$searchWhere['customerSettlementDateEnd']} 23:59:59' ";
        }
        if (!empty($searchWhere['payTimeStart'])) {
            $where .= " and orders.pay_time>='{$searchWhere['payTimeStart']} 00:00:00' ";
        }
        if (!empty($searchWhere['payTimeEnd'])) {
            $where .= " and orders.pay_time<='{$searchWhere['payTimeEnd']} 23:59:59' ";
        }
        if (is_numeric($searchWhere['customerSettlementStatus'])) {
            if ($searchWhere['customerSettlementStatus'] == 1) {
                $where .= " and orders.customerSettlementId>0 ";
            } else {
                $where .= " and orders.customerSettlementId=0 ";
            }
        }
        if (is_numeric($searchWhere['deliveryStatus'])) {
            $where .= " and orders.deliveryStatus={$searchWhere['deliveryStatus']} ";
        }
        if (is_numeric($searchWhere['returnStatus'])) {
            $where .= " and orders.returnStatus={$searchWhere['returnStatus']} ";
        }
        if (!empty($searchWhere['lineId'])) {
            $where .= " and orders.lineId={$searchWhere['lineId']} ";
        }
        if (!empty($searchWhere['driverId'])) {
            $where .= " and orders.driverId={$searchWhere['driverId']} ";
        }
        if (!empty($searchWhere['rankId'])) {
            $where .= " and users.rankId={$searchWhere['rankId']} ";
        }
        if (!empty($searchWhere['payFrom'])) {
            $payFromStr = implode(',', $searchWhere['payFrom']);
            $where .= " and orders.payFrom IN({$payFromStr}) ";
        }
        $field = 'orders.orderId,orders.orderNo,orders.userName as receivingName,orders.userPhone as receivingPhone,orders.receivableAmount,orders.receivedAmount,orders.paidInAmount,orders.zeroAmount,orders.uncollectedAmount,orders.requireTime,orders.customerSettlementDate,orders.driverId,orders.lineId,orders.payFrom,orders.customerSettlementId,orders.orderRemarks,orders.createTime,orders.deliverMoney,orders.diffAmount,orders.returnAmount';
        $field .= ',users.userName as customerName';
        $sql = $ordersModel
            ->alias('orders')
            ->join('left join wst_users users on users.userId=orders.userId')
            ->where($where)
            ->field($field)
            ->order('orders.createTime desc')
            ->buildSql();
        if ($searchWhere['usePage'] == 1) {
            $result = $ordersModel->pageQuery($sql, $searchWhere['page'], $searchWhere['pageSize']);
            $list = $result['root'];
            $result['currPageSum'] = array(//当前页合计
                'receivableAmountSum' => formatAmountNum(array_sum(array_column($list, 'receivableAmount'))),//应收金额合计
                'receivedAmountSum' => formatAmountNum(array_sum(array_column($list, 'receivedAmount'))),//已收金额合计
                'paidInAmountSum' => formatAmountNum(array_sum(array_column($list, 'paidInAmount'))),//实收金额合计
                'zeroAmountSum' => formatAmountNum(array_sum(array_column($list, 'zeroAmount'))),//抹零金额合计
                'uncollectedAmountSum' => formatAmountNum(array_sum(array_column($list, 'uncollectedAmount'))),//未收金额合计
            );
            $allReceivableAmount = $ordersModel
                ->alias('orders')
                ->join('left join wst_users users on users.userId=orders.userId')
                ->where($where)
                ->sum('orders.receivableAmount');
            $allReceivedAmount = $ordersModel
                ->alias('orders')
                ->join('left join wst_users users on users.userId=orders.userId')
                ->where($where)
                ->sum('orders.receivedAmount');
            $allPaidInAmount = $ordersModel
                ->alias('orders')
                ->join('left join wst_users users on users.userId=orders.userId')
                ->where($where)
                ->sum('orders.paidInAmount');
            $allZeroAmount = $ordersModel
                ->alias('orders')
                ->join('left join wst_users users on users.userId=orders.userId')
                ->where($where)
                ->sum('orders.zeroAmount');
            $allUncollectedAmount = $ordersModel
                ->alias('orders')
                ->join('left join wst_users users on users.userId=orders.userId')
                ->where($where)
                ->sum('orders.uncollectedAmount');
            $result['allPageSum'] = array(//所有页合计
                'receivableAmountSum' => formatAmountNum($allReceivableAmount),//应收金额合计
                'receivedAmountSum' => formatAmountNum($allReceivedAmount),//已收金额合计
                'paidInAmountSum' => formatAmountNum($allPaidInAmount),//实收金额合计
                'zeroAmountSum' => formatAmountNum($allZeroAmount),//抹零金额合计
                'uncollectedAmountSum' => formatAmountNum($allUncollectedAmount),//未收金额合计
            );
        } else {
            $list = $ordersModel->query($sql);
            $result = array(
                'root' => $list
            );
        }
        $lineModule = new LineModule();
        $driverModule = new DriverModule();
        $ordersModule = new OrdersModule();
        foreach ($result['root'] as &$item) {
            $item['billTypeName'] = '销售订单';
            $item['customerSettlementDate'] = is_null($item['customerSettlementDate']) ? '' : $item['customerSettlementDate'];
            $item['lineName'] = '';
            if (!empty($item['lineId'])) {
                $lineRow = $lineModule->getLineDetailById($item['lineId']);
                $item['lineName'] = !empty($lineRow) ? $lineRow['lineName'] : '';
            }
            $item['driverName'] = '';
            if (!empty($item['driverId'])) {
                $driverRow = $driverModule->getDriverDetailById($item['driverId'], 'driverName');
                $item['driverName'] = !empty($driverRow) ? $driverRow['driverName'] : '';
            }
            $item['payFromName'] = OrderEnum::getPayFromName()[$item['payFrom']];
            $item['customerSettlementStatus'] = '未结算';
            $item['customerSettlementStatusVal'] = 0;
            if (!empty($item['customerSettlementId'])) {
                $item['customerSettlementStatus'] = '已结算';
                $item['customerSettlementStatusVal'] = 1;
            }
            $item['return_bill_list'] = array();//退货信息
            $orderComplainsList = $ordersModule->getComplainsListByOrderId($item['orderId']);
            if (!empty($orderComplainsList)) {
                $returnBillList = array();
                foreach ($orderComplainsList as $returnRow) {
                    $returnBillRow = array(
                        'complainId' => $returnRow['complainId'],
                        'customerName' => $item['customerName'],
                        'receivingPhone' => $item['receivingPhone'],
                        'billTypeName' => '订单退货',
                        'receivableAmount' => formatAmountNum(-$returnRow['returnAmount']),
                        'receivedAmount' => formatAmountNum(-$returnRow['returnAmount']),
                        'paidInAmount' => formatAmountNum(-$returnRow['returnAmount']),
                        'zeroAmount' => formatAmountNum(0),
                        'uncollectedAmount' => formatAmountNum(0),
                        'requireTime' => $item['requireTime'],
                        'lineName' => $item['lineName'],
                        'payFromName' => $item['payFromName'],
                        'customerSettlementStatus' => $returnRow['returnAmountStatus'] == 1 ? "已结算" : "未结算",
                        'billRemark' => $returnRow['complainContent'],
                    );
                    $returnBillList[] = $returnBillRow;
                }
                $item['return_bill_list'] = $returnBillList;
            }
        }
        unset($item);
        return $result;
    }

    /**
     * 订单关联的客户结算单数据
     * @param int $orderId 订单id
     * @param string $field 表字段
     * @return arrayr
     * */
    public function getOrderSettlementList(int $orderId, $field = '*')
    {
        $model = new CustomerSettlementBillModel();
        $where = array(
            'isDelete' => 0,
            'orderId' => $orderId,
        );
        $result = $model->where($where)->field($field)->select();
        return (array)$result;
    }

    /**
     * 客户结算-结算-确认结算
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int payType 支付方式(1:支付宝，2：微信 3:余额  4:货到付款 5:现金 6:转账 7:线下支付宝 8:线下微信)
     * -string payerName 交款人
     * -array billPic 单据照片
     * -string billRemark 单据备注
     * -array settlementList 结算单信息
     * @param object $trans
     * @return array
     * */
    public function doCustomerSettlement(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $businessParams = array(
            'loginInfo' => array(),
            'payType' => 0,
            'payerName' => '',
            'billPic' => array(),
            'billRemark' => '',
            'settlementList' => array(),
        );
        parm_filter($businessParams, $paramsInput);
        $loginInfo = $businessParams['loginInfo'];
        if (empty($loginInfo)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '参数有误');
        }
        $billPic = implode(',', $businessParams['billPic']);
        $payerName = $businessParams['payerName'];
        $billRemark = $businessParams['billRemark'];
        $settlementList = $businessParams['settlementList'];
        $ordersModule = new OrdersModule();
        foreach ($settlementList as $item) {
            $dataId = (int)$item['dataId'];
            $billType = (int)$item['billType'];//类型(1:销售订单 2:退货单)
            if ($billType == 1) {//销售订单
                $orderRow = $ordersModule->getOrderInfoById($dataId, '*', 2);
                if (empty($orderRow)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单异常');
                }
                if (!empty($orderRow['customerSettlementId'])) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已结算过的订单请勿重复结算');
                }
            }
            if ($billType == 2) {//订单退货

            }
            //创建客户结算单
            $settlementData = array(
                'loginInfo' => $loginInfo,
                'relation_order_id' => $dataId,
                'billFrom' => $billType,
                'ignoreType' => 1,
                'settlementRemark' => $item['settlementRemark'],
                'zeroAmount' => $item['zeroAmount'],
                'realAmount' => $item['realAmount'],
                'payType' => $businessParams['payType'],
                'billPic' => $billPic,
                'billRemark' => $businessParams['billRemark'],
                'payerName' => $payerName,
            );
            $addSettlementRes = $this->createCustomerSerrlementBill($settlementData, $dbTrans);
            if ($addSettlementRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '结算单生成失败');
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }
}