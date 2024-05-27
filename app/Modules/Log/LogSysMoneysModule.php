<?php


namespace App\Modules\Log;

use App\Models\LogSysMoneysModel;
use App\Models\LogUserLoginsModel;
use App\Modules\Base;
use Think\Model;

//平台流水记录类
class LogSysMoneysModule extends Base
{
    /**
     * 获取平台流水记录详情-根据数据记录id查找
     * @param int $dataId 数据记录id
     * @param string $field 表字段
     * @return array
     * */
    public function getDetailByDataId(int $dataId, $field = '*')
    {
        $model = new LogSysMoneysModel();
        $detail = $model->where(array(
            'dataId' => $dataId,
            'dataFlag' => 1,
        ))->field($field)->find();
        return (array)$detail;
    }

    /**
     * 平台流水日志-保存
     * @param array $params
     * -wst_log_sys_moneys表字段
     * @return int
     * */
    public function saveLogSysMoneys(array $params, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $saveParams = array(
            'targetType' => null,
            'targetId' => null,
            'dataSrc' => null,
            'dataId' => null,
            'moneyRemark' => null,
            'moneyType' => null,
            'money' => null,
            'dataFlag' => null,
            'state' => null,
            'payType' => null,
        );
        parm_filter($saveParams, $params);
        $model = new LogSysMoneysModel();
        if (empty($params['moneyId'])) {
            $saveParams['createTime'] = date('Y-m-d H:i:s');
            $moneyId = $model->add($saveParams);
            if (empty($moneyId)) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $moneyId = $params['moneyId'];
            $where = array(
                'moneyId' => $moneyId
            );
            $saveRes = $model->where($where)->save($saveParams);
            if ($saveRes === false) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$moneyId;
    }
}