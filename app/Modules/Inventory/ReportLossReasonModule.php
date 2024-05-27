<?php

namespace App\Modules\Inventory;


use App\Enum\ExceptionCodeEnum;
use App\Models\ReportLossReasonModel;
use CjsProtocol\LogicResponse;
use App\Models\BaseModel;
use Think\Model;

/**
 * 报损原因
 * Class ReportLossReasonModule
 * @package App\Modules\Inventory
 */
class ReportLossReasonModule extends BaseModel
{

    /**
     * 获取报损原因列表
     * @param int $shop_id 门店id
     * @param string $field 表字段
     * */
    public function getLossReasonList(int $shop_id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new ReportLossReasonModel();
        $where = array(
            'shopId' => $shop_id,
            'rlrFlag' => 1,
        );
        $result = $model->where($where)->order('rlrid desc')->field($field)->select();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无报损原因')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }

    /**
     * 根据报损原因id获取报损原因详情
     * @param int $id
     * @param string $field 表字段
     * */
    public function getLossReasonInfoById(int $id, $field = '*')
    {
        $response = LogicResponse::getInstance();
        $model = new ReportLossReasonModel();
        $where = array(
            'rlrid' => $id,
            'rlrFlag' => 1,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return $response->setCode(ExceptionCodeEnum::FAIL)->setMsg('暂无报损原因')->toArray();
        }
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setMsg('成功')->setData($result)->toArray();
    }
}