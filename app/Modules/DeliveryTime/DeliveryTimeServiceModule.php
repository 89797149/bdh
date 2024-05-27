<?php

namespace App\Modules\DeliveryTime;


use App\Models\BaseModel;

/**
 * Class DeliveryTimeServiceModule
 * @package App\Modules\AuthRule
 * 统一提供给内部其他模块使用的区域类
 */
class DeliveryTimeServiceModule extends BaseModel
{
    /**
     * @param $shopId
     * @return mixed
     * 根据店铺id获取配送时间分类列表
     */
    public function getDeliveryTimeTypeListByShopId($shopId)
    {
        $deliveryTimeModule = new DeliveryTimeModule();
        $data = $deliveryTimeModule->getDeliveryTimeTypeListByShopId((int)$shopId);
        return $data;
    }

    /**
     * @param $id
     * @return array
     * 根据自增id获取配送时间分类详情
     */
    public function getDeliveryTimeTypeInfo($id)
    {
        $deliveryTimeModule = new DeliveryTimeModule();
        $data = $deliveryTimeModule->getDeliveryTimeTypeInfo((int)$id);
        return $data;
    }

    /**
     * @param $params
     * @return array
     * 根据自增id更新配送时间分类信息
     */
    public function editDeliveryTimeTypeInfo($params)
    {
        $deliveryTimeModule = new DeliveryTimeModule();
        $data = $deliveryTimeModule->editDeliveryTimeTypeInfo($params);
        return $data;
    }

    /**
     * @param $deliveryTimeTypeId
     * @return array
     * 根据配送时间分类id获取配送时间列表
     */
    public function getDeliveryTimeListById($deliveryTimeTypeId)
    {
        $deliveryTimeModule = new DeliveryTimeModule();
        $data = $deliveryTimeModule->getDeliveryTimeListById((int)$deliveryTimeTypeId);
        return $data;
    }

    /**
     * @param $id
     * @return array
     * 根据自增id获取配送时间分类详情
     */
    public function getDeliveryTimeInfo($id)
    {
        $deliveryTimeModule = new DeliveryTimeModule();
        $data = $deliveryTimeModule->getDeliveryTimeInfo((int)$id);
        return $data;
    }


    /**
     * @param $params
     * @return array
     * 根据自增id更新配送时间分类信息
     */
    public function editDeliveryTimeInfo($params)
    {
        $deliveryTimeModule = new DeliveryTimeModule();
        $data = $deliveryTimeModule->editDeliveryTimeInfo($params);
        return $data;
    }
}