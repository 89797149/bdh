<?php
/**
 * 司机
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-24
 * Time: 15:25
 */

namespace App\Modules\PSD;


use App\Models\DriverModel;

class DriverModule
{
    /**
     * 司机-司机详情-id获取
     * @param int $driverId 司机id
     * @param string $field 表字段
     * @param int $verificationDelete 是否验证删除状态(0:不验证 1:验证)
     * @return array
     * */
    public function getDriverDetailById(int $driverId, $field = '*', $verificationDelete = 1)
    {
        $model = new DriverModel();
        $where = array(
            'driverId' => $driverId
        );
        if ($verificationDelete == 1) {
            $where['dataFlag'] = 1;
        }
        $result = $model->where(array($where))->field($field)->find();
        return (array)$result;
    }

    /**
     * 司机列表
     * @param int $shopId 门店id
     * @param string $field
     * @return array
     * */
    public function getDriverDetailList(int $shopId, $field = '*')
    {
        $model = new DriverModel();
        $where = array(
            'dataFlag' => 1,
            'shopId' => $shopId,
        );
        $result = $model->where(array($where))->field($field)->select();
        return $result;
    }
}