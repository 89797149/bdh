<?php

namespace App\Modules\DeliveryTime;


use App\Enum\ExceptionCodeEnum;
use App\Models\AuthRuleModel;
use App\Models\BaseModel;
use App\Models\DeliveryTimeModel;
use App\Models\DeliveryTimeTypeModel;
use App\Models\RolesModel;
use CjsProtocol\LogicResponse;
use http\Client\Response;

/**
 * Class DeliveryTimeModule
 * @package App\Modules\AuthRule
 * 该类只为DeliveryTimeServiceModule类服务
 */
class DeliveryTimeModule extends BaseModel
{
    /**
     * @param $shopId
     * @return array
     * 根据店铺id获取配送时间列表
     */
    public function getDeliveryTimeTypeListByShopId($shopId)
    {
        $response = LogicResponse::getInstance();
        $deliveryTimeTypeModel = new DeliveryTimeTypeModel();
        $where = [];
        $where['shopId'] = $shopId;
        $rest = $deliveryTimeTypeModel->where($where)->order('sort asc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$rest)->toArray();
    }

    /**
     * @param $id
     * @return array
     * 根据自增id获取配送时间分类详情
     */
    public function getDeliveryTimeTypeInfo($id,$data_type=1)
    {
        $response = LogicResponse::getInstance();
        $deliveryTimeTypeModel = new DeliveryTimeTypeModel();
        $where = [];
        $where['id'] = $id;
        $rest = $deliveryTimeTypeModel->where($where)->find();
        if($data_type == 1){
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
        }else{
            return (array)$rest;
        }
    }

    /**
     * @param $params
     * @return array
     * 根据自增id更新配送时间分类信息
     */
    public function editDeliveryTimeTypeInfo($params)
    {
        $response = LogicResponse::getInstance();
        $deliveryTimeTypeModel = new DeliveryTimeTypeModel();

        $where = [];
        $where['id'] = $params['id'];

        $param = [];
        $param['typeName'] = null;//分类名
        $param['sort'] = null;//排序
        $param['number'] = null;//天数【从当天累加】

        parm_filter($param, $params);

        $rest = $deliveryTimeTypeModel->where($where)->save($param);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
    }

    /**
     * @param $deliveryTimeTypeId
     * @return array
     * 根据配送时间分类id获取配送时间列表
     */
    public function getDeliveryTimeListById($deliveryTimeTypeId)
    {
        $response = LogicResponse::getInstance();
        $deliveryTimeModel = new DeliveryTimeModel();
        $where = [];
        $where['deliveryTimeTypeId'] = $deliveryTimeTypeId;
        $rest = $deliveryTimeModel->where($where)->order('sort asc')->select();
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData((array)$rest)->toArray();
    }

    /**
     * @param $id
     * @return array
     * 根据自增id获取配送时间详情
     */
    public function getDeliveryTimeInfo($id,$data_type=1)
    {
        $response = LogicResponse::getInstance();
        $deliveryTimeModel = new DeliveryTimeModel();
        $where = [];
        $where['id'] = $id;
        $rest = $deliveryTimeModel->where($where)->find();
        if($data_type == 1){
            return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
        }else{
            return (array)$rest;
        }
    }

    /**
     * @param $params
     * @return array
     * 根据自增id更新配送时间信息
     */
    public function editDeliveryTimeInfo($params)
    {
        $response = LogicResponse::getInstance();
        $deliveryTimeModel = new DeliveryTimeModel();

        $where = [];
        $where['id'] = $params['id'];

        $param = [];
        $param['timeStart'] = null;//时间点开始（时分）
        $param['timeEnd'] = null;//时间点结束（时分）
        $param['sort'] = null;//排序

        parm_filter($param, $params);

        $rest = $deliveryTimeModel->where($where)->save($param);
        return $response->setCode(ExceptionCodeEnum::SUCCESS)->setData($rest)->toArray();
    }
}