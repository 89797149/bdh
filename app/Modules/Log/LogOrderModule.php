<?php


namespace App\Modules\Log;

use App\Models\LogOrderModel;
use App\Models\LogOrdersModel;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use Think\Model;

//订单日志类
class LogOrderModule extends Base
{
    /**
     * 根据订单id获取订单日志
     * @param int $orderId
     * @return array
     */
    public function getLogOrderList($orderId)
    {
        $response = LogicResponse::getInstance();
        $log_order_model = new LogOrdersModel();
        $data = $log_order_model
            ->where(['orderId' => $orderId])
            ->order('logTime', 'asc')
            ->select();
        return $response->setData($data)->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }


    /**
     * 新增订单日志
     * @param array $params <p>
     * int orderId 订单id
     * string logContent 内容描述
     * int logUserId 操作者id
     * string logUserName 操作者姓名
     * int orderStatus 订单状态 同订单表一致
     * int payStatus 支付状态 支付状态【0：未支付|1：已支付|2：已退款】
     * int logType 类型【0:用户|1:商家平台|2:系统|3:司机】
     * </p>
     * @param object $trans
     * @return bool
     * */
    public function addLogOrders(array $params, $trans = null)
    {
        if (empty($trans)) {
            $m = new Model();
            $m->startTrans();
        } else {
            $m = $trans;
        }
        $save = array(
            'orderId' => null,
            'logContent' => null,
            'logUserId' => null,
            'logUserName' => null,
            'orderStatus' => null,
            'payStatus' => null,
            'logType' => null,
            'logTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($save, $params);
        $model = new LogOrdersModel();
        $log_id = $model->add($save);
        if (!$log_id) {
            $m->rollback();
            return false;
        }
        if (empty($trans)) {
            $m->commit();
        }
        return true;
    }

    /**
     * 订单日志-删除订单日志-多条件
     * @param array $params
     * -int orderId 订单id
     * -int orderStatus 订单状态
     * @return bool
     * */
    public function delLogOrdersByParams(array $params, $trans)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $where = array(
            'orderId' => null,
            'orderStatus' => null,
        );
        parm_filter($where, $params);
        if (empty($where)) {
            $db_trans->rollback();
            return false;
        }
        $model = new LogOrdersModel();
        $result = $model->where($where)->delete();
        if ($result === false) {
            $db_trans->rollback();
            return false;
        }
        if (empty($trans)) {
            $db_trans->commit();
        }
        return true;
    }
}