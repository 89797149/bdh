<?php
/**
 * 配送单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-06-29
 * Time: 9:48
 */

namespace App\Modules\PSD;


use App\Models\BaseModel;
use App\Models\OrdersModel;
use App\Models\WaveTaskModel;
use Think\Model;

class TaskModule extends BaseModel
{
    /**
     * 配送单-详情-id获取
     * @param int $task_id 配送单id
     * @param string $field 表字段
     * @return array
     * */
    public function getTaskDetailById(int $task_id, $field = '*')
    {
        $model = new WaveTaskModel();
        $result = $model->where(array('taskId' => $task_id))->field($field)->find();
        if (empty($result)) {
            return array();
        }
        return $result;
    }

    /**
     * 配送单-是否已接单
     * @param int $taskId 配送单id
     * @return bool
     * */
    public function isReceived(int $taskId)
    {
        $where = array(
            'taskId' => $taskId,
            'taskStatus' => 1,
        );
        $count = M('psd_received')->where($where)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * 配送单-更新配送单的配送状态为已完成
     * @param int $taskId 配送单id
     * @param object $trans
     * @return bool
     * */
    public function completeTaskDeliveryStatus(int $taskId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new WaveTaskModel();
//        $orderModel = new OrdersModel();
        $orderCount = M('orders')
            ->where(array('taskId' => $taskId))
            ->count();
        if ($orderCount > 0) {
            $completeOrderCount = M('orders')
                ->where(array('taskId' => $taskId, 'orderStatus' => 4))
                ->count();
            if ($completeOrderCount >= $orderCount) {
                $saveParams = array(
                    'deliveryStatus' => 2,
                    'updateTime' => date('Y-m-d H:i:s'),
                );
                $model->where(array('taskId' => $taskId))->save($saveParams);
                M('psd_timeline')->add(
                    array(
                        'operationName' => '系统',
                        'operationType' => 1,
                        'tableName' => 'wst_psd_wave_task',
                        'dataId' => $taskId,
                        'fieldCode' => 'deliveryStatus',
                        'fieldValue' => 2,
                        'operationContent' => '系统：更改配送任务为已完成',
                        'operationTime' => date('Y-m-d H:i:s'),
                    )
                );
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }
}