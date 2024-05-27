<?php
/**
 * 结算单(线上)
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-03
 * Time: 14:27
 */

namespace App\Modules\Settlement;


use App\Enum\Settlement\SettlementBillEnum;
use App\Models\OrdersModel;
use App\Models\SettlementBillModel;
use App\Modules\Orders\OrdersModule;
use Think\Model;

class SettlementBillModule
{
    //线上结算相关的统一写在这里,方便给新旧客户更新
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
     * -date [settlementId] 结算单Id
     * -int [usePage] 使用分页(0:不使用 1:使用)
     * -date [page] 页码
     * -date [pageSize] 分页条数
     * @return array
     * */
    public function getSettlementOrderList(array $paramsInput)
    {
        $shopId = $paramsInput['shopId'];
        $model = new OrdersModel();
        $where = "orders.orderFlag=1 and orders.orderStatus=4 and orders.shopId={$shopId} and orders.payType=1 and orders.realTotalMoney>0 ";
        $where .= ' and users.userFlag=1 ';
        if (!empty($paramsInput['billNo'])) {
            $where .= " and settlement.billNo like '%{$paramsInput['billNo']}%' ";
        }
        if (!empty($paramsInput['customerName'])) {
            $where .= " and users.userName like '%{$paramsInput['customerName']}%' ";
        }
        if (!empty($paramsInput['orderNo'])) {
            $where .= " and orders.orderNo like '%{$paramsInput['orderNo']}%' ";
        }
        if (!empty($paramsInput['receivingName'])) {
            $where .= " and orders.userName like '%{$paramsInput['receivingName']}%' ";
        }
        if (!empty($paramsInput['receivingPhone'])) {
            $where .= " and orders.userPhone like '%{$paramsInput['receivingPhone']}%' ";
        }
        if (!empty($paramsInput['createOrderDateStart'])) {
            $where .= " and orders.createTime >= '{$paramsInput['createOrderDateStart']} 00:00:00' ";
        }
        if (!empty($paramsInput['createOrderDateEnd'])) {
            $where .= " and orders.createTime <= '{$paramsInput['createOrderDateEnd']} 23:59:59' ";
        }
        if (!empty($paramsInput['settlementId'])) {
            $where .= " and orders.settlementId={$paramsInput['settlementId']}";
        }
        if (isset($paramsInput['settlementStatus'])) {
            if (is_numeric($paramsInput['settlementStatus'])) {
                if ($paramsInput['settlementStatus'] == 0) {
                    $where .= " and orders.settlementId=0 ";
                }
                if ($paramsInput['settlementStatus'] == 1) {
                    $where .= " and orders.settlementId>0 ";
                }
            }
        }
        if (!empty($paramsInput['applyDateStart'])) {
            $where .= " and settlement.createTime >= '{$paramsInput['applyDateStart']}'";
        }
        if (!empty($paramsInput['applyDateEnd'])) {
            $where .= " and settlement.createTime <= '{$paramsInput['applyDateEnd']}'";
        }
        if (!empty($paramsInput['settlementDateStart'])) {
            $where .= " and settlement.settlementTime >= '{$paramsInput['settlementDateStart']}'";
        }
        if (!empty($paramsInput['settlementDateEnd'])) {
            $where .= " and settlement.settlementTime <= '{$paramsInput['settlementDateEnd']}'";
        }
        $field = 'orders.orderId,orders.orderNo,orders.userName as receivingName,orders.userPhone as receivingPhone,orders.createTime,orders.poundageMoney,orders.realTotalMoney';
        $field .= ',users.userName as customerName';
        $field .= ',settlement.createTime as applyTime,settlement.settlementTime';
        $sql = $model
            ->alias('orders')
            ->join("left join wst_users users on orders.userId=users.userId")
            ->join("left join wst_settlement_bill settlement on settlement.settlementId=orders.settlementId")
            ->where($where)
            ->field($field)
            ->buildSql();
        if ($paramsInput['usePage'] === 0) {
            $list = $model->query($sql);
            $result['root'] = (array)$list;
        } else {
            $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        }
        $orderModule = new OrdersModule();
        foreach ($result['root'] as &$item) {//统一返回字符串
            $item['applyTime'] = (string)$item['applyTime'];
            $item['settlementTime'] = (string)$item['settlementTime'];
            $orderGoodsDiff = $orderModule->getOrderGoodsDiffList($item['orderId'], 1);
            $item['orderDiffAmount'] = (string)array_sum(array_column($orderGoodsDiff, 'money'));
            $item['settlementAmount'] = bc_math($item['realTotalMoney'], $item['orderDiffAmount'], 'bcsub', 2);
            $item['settlementAmount'] = bc_math($item['settlementAmount'], $item['poundageMoney'], 'bcsub', 2);
        }
        unset($item);
        if ($paramsInput['usePage'] === 0) {
            $result = $result['root'];
        }
        return $result;
    }

    /**
     * 结算单-保存单据信息
     * @param array $paramsInput
     * wst_settlement_bill表字段
     * @param object $trans
     * @return int
     * */
    public function saveSettlementBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'shopId' => null,
            'bankName' => null,
            'bankCardNo' => null,
            'bankCardOwner' => null,
            'orderAmount' => null,
            'orderDiffAmount' => null,
            'poundageAmount' => null,
            'settlementAmount' => null,
            'settlementStatus' => null,
            'remark' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['settlementStatus'])) {
            if ($saveParams['settlementStatus'] == 1) {
                $saveParams['settlementTime'] = $datetime;
            }
        }
        $model = new SettlementBillModel();
        if (empty($paramsInput['settlementId'])) {
            $saveParams['createTime'] = $datetime;
            $settlementId = $model->add($saveParams);
            if (!$settlementId) {
                $dbTrans->rollback();
                return 0;
            }
            $saveParams = array(
                'settlementId' => $settlementId,
                'billNo' => 'JS' . date('Ymd') . str_pad($settlementId, 10, "0", STR_PAD_LEFT),
            );
            $saveRes = $model->where(array('settlementId' => $settlementId))->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $settlementId = $paramsInput['settlementId'];
            $saveRes = $model->where(array('settlementId' => $settlementId))->save($saveParams);
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
     * 结算单-结算单列表
     * @param array $paramsInput
     * int shopId 门店id
     * string billNo 结算单号
     * int settlementStatus 结算状态(0:未结算 1:已结算) 不传默认全部
     * date createDateStart 申请结算日期-开始日期
     * date createDateEnd 申请结算日期-结束日期
     * int page 页码
     * int pageSize 分页条数
     * @return array
     * */
    public function getSettlementBillList(array $paramsInput)
    {
        $where = " 1=1 ";
        if (!empty($paramsInput['shopId'])) {
            $shopId = (int)$paramsInput['shopId'];
            $where = "shopId={$shopId}";
        }
        if (!empty($paramsInput['billNo'])) {
            $where .= " and billNo like '{$paramsInput['billNo']}' ";
        }
        if (isset($paramsInput['settlementStatus'])) {
            if (is_numeric($paramsInput['settlementStatus'])) {
                $where .= " and settlementStatus = {$paramsInput['settlementStatus']}";
            }
        }
        if (!empty($paramsInput['createDateStart'])) {
            $where .= " and createTime >= '{$paramsInput['createDateStart']} 00:00:00'";

        }
        if (!empty($paramsInput['createDateEnd'])) {
            $where .= " and createTime <= '{$paramsInput['createDateEnd']} 23:59:59'";
        }
        if (!empty($paramsInput['settlementDateStart'])) {
            $where .= " and settlementTime >= '{$paramsInput['settlementDateStart']} 00:00:00'";

        }
        if (!empty($paramsInput['settlementDateEnd'])) {
            $where .= " and settlementTime <= '{$paramsInput['settlementDateStart']} 23:59:59'";
        }
        $field = 'settlementId,shopId,billNo,bankName,bankCardNo,bankCardOwner,orderAmount,orderDiffAmount,poundageAmount,settlementAmount,settlementStatus,createTime,settlementTime';
        $model = new SettlementBillModel();
        $sql = $model
            ->where($where)
            ->field($field)
            ->buildSql();
        $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        $settlementBillEnum = new SettlementBillEnum();
        foreach ($result['root'] as &$val) {
            $val['settlementTime'] = (string)$val['settlementTime'];
            $val['settlementStatusName'] = $settlementBillEnum::getSettlementStatus()[$val['settlementStatus']];
        }
        unset($val);
        return $result;
    }

    /**
     * 结算单-结算单详情
     * @param int $settlementId 结算单id
     * @param string $field
     * @return array
     * */
    public function getSettlementBillDetail(int $settlementId, $field = '*')
    {
        $where = array(
            'settlementId' => $settlementId,
        );
        $model = new SettlementBillModel();
        $result = $model->where($where)->field($field)->find();
        if (is_null($result['settlementTime'])) {
            $result['settlementTime'] = (string)$result['settlementTime'];
        }
        if (isset($result['settlementStatus'])) {
            $result['settlementStatusName'] = (new SettlementBillEnum())->getSettlementStatus()[$result['settlementStatus']];
        }
        return (array)$result;
    }
}