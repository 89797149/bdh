<?php


namespace App\Modules\Log;

use App\Models\TableActionLogModel;
use CjsProtocol\LogicResponse;
use App\Enum\ExceptionCodeEnum;
use App\Modules\Base;
use Think\Model;

//表操作日志类-为LogServiceModule类服务
class TableActionLogModule extends Base
{
    /**
     * 添加表操作日志
     * @param array $params 表字段
     * @param object $trans
     * @return array
     * */
    public function addTableActionLog(array $params, $trans = null)
    {
        $response = LogicResponse::getInstance();
        if (!empty($trans)) {
            $model = $trans;
        } else {
            $model = new Model();
            $model->startTrans();
        }
        $table_action_log_tab = new TableActionLogModel();
        $save = array(
            'tableName' => '',
            'dataId' => 0,
            'actionUserId' => 0,
            'actionUserName' => '',
            'fieldName' => '',
            'fieldValue' => '',
            'remark' => '',
            'createTime' => date('Y-m-d H:i:s'),
        );
        parm_filter($save, $params);
        $logId = $table_action_log_tab->add($save);
        if (!$logId) {
            $model->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('表日志记录失败')->toArray();
        }
        if (empty($trans)) {
            $model->commit();
        }
        $data = array(
            'logId' => $logId
        );
        return $response->setData($data)->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * @param string $tableName 表名
     * @param int $complainId 操作人id
     * @return array
     * 获取退货/退款操作日志列表
     */
    public function getTableActionLogList(string $tableName, int $complainId)
    {
        $where = [];
        $where['tableName'] = $tableName;
        $where['dataId'] = $complainId;
        $response = LogicResponse::getInstance();
        $table_action_log_tab = new TableActionLogModel();
        $data = $table_action_log_tab->where($where)->select();
        return $response->setData($data)->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 表操作日志-删除
     * @param string $tableName 表名
     * @param int $dataId 数据id
     * @param object $trans
     * @return array
     */
    public function delTableActionLog(string $tableName, int $dataId, $trans = null)
    {
        if (empty($trans)) {
            $db_trans = new Model();
            $db_trans->startTrans();
        } else {
            $db_trans = $trans;
        }
        $where = array();
        $where['tableName'] = $tableName;
        $where['dataId'] = $dataId;
        $response = LogicResponse::getInstance();
        $table_action_log_tab = new TableActionLogModel();
        $result = $table_action_log_tab->where($where)->delete();
        if (!$result) {
            $db_trans->rollback();
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('删除失败')->toArray();
        }
        return $response->setData(ExceptionCodeEnum::SUCCESS)->setCode(ExceptionCodeEnum::SUCCESS)->toArray();
    }

    /**
     * 操作日志列表-多条件获取
     * @param array $params
     * -string tableName 表名称
     * -int dataId 数据id
     * -string fieldName 字段名称
     * -string fieldValue 字段值
     * @return array
     */
    public function getLogListByParams(array $params, $field = '*')
    {
        $where = array(
            'tableName' => null,
            'dataId' => null,
            'fieldName' => null,
            'fieldValue' => null,
        );
        parm_filter($where, $params);
        $log_order_model = new TableActionLogModel();
        $data = $log_order_model
            ->where($where)
            ->field($field)
            ->order('createTime', 'asc')
            ->select();
        return (array)$data;
    }

    /**
     * 操作日志详情-多条件获取
     * @param array $params
     * -string tableName 表名称
     * -int dataId 数据id
     * -string fieldName 字段名称
     * -string fieldValue 字段值
     * @return array
     */
    public function getLogDetailByParams(array $params)
    {
        $where = array(
            'tableName' => null,
            'dataId' => null,
            'fieldName' => null,
            'fieldValue' => null,
        );
        parm_filter($where, $params);
        $log_order_model = new TableActionLogModel();
        $data = $log_order_model
            ->where($where)
            ->find();
        return (array)$data;
    }
}