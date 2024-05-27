<?php

namespace Merchantapi\Model;

use App\Modules\DeliveryTime\DeliveryTimeServiceModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 配送时间类
 */
class DeliveryTimeModel extends BaseModel
{
    /**
     * @param $shopId
     * @return mixed
     * 获取配送时间分类列表
     */
    public function getTypeList($shopId)
    {
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeTypeListByShopId = $deliveryTimeServiceModule->getDeliveryTimeTypeListByShopId($shopId);
        $data = $getDeliveryTimeTypeListByShopId['data'];
        return $data;
    }

    /**
     * @param $addData
     * @return mixed
     * 添加配送时间分类
     */
    public function addType($addData)
    {
        $data = M("delivery_time_type")->add($addData);
        return $data;
    }

    /**
     * @param $id
     * @return mixed
     * 获取配送时间分类详情
     */
    public function getTypeInfo($id)
    {
        $where['id'] = $id;
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeTypeInfo = $deliveryTimeServiceModule->getDeliveryTimeTypeInfo($id);
        if (empty($getDeliveryTimeTypeInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        return returnData($getDeliveryTimeTypeInfo['data']);
    }

    /**
     * @param $id
     * @return mixed
     * 删除配送时间分类
     */
    public function deleteType($id)
    {
        $where['id'] = $id;
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeTypeInfo = $deliveryTimeServiceModule->getDeliveryTimeTypeInfo($id);
        if (empty($getDeliveryTimeTypeInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $data = M("delivery_time_type")->where($where)->delete();
        return returnData(true);
    }

    /**
     * @param $saveData
     * @return mixed
     * 更新配送时间分类
     */
    public function updateType($saveData)
    {
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeTypeInfo = $deliveryTimeServiceModule->getDeliveryTimeTypeInfo($saveData['id']);
        if (empty($getDeliveryTimeTypeInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $deliveryTimeServiceModule->editDeliveryTimeTypeInfo($saveData);
        return returnData(true);
    }

    /**
     * @param $id
     * @return mixed
     * 获取时间点列表
     */
    public function getDeliveryTimeList($id)
    {
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeTypeInfo = $deliveryTimeServiceModule->getDeliveryTimeTypeInfo($id);
        if (empty($getDeliveryTimeTypeInfo['data'])) {
            $this->ajaxReturn(returnData(false, -1, 'error', '暂无相关数据'));
        }
        $getDeliveryTimeListById = $deliveryTimeServiceModule->getDeliveryTimeListById($id);
        return $getDeliveryTimeListById['data'];
    }

    /**
     * @param $addData
     * @return mixed
     * 添加时间点
     */
    public function addDeliveryTime($addData)
    {
        $data = M("delivery_time")->add($addData);
        return $data;
    }

    /**
     * @param $id
     * @return mixed
     * 获取时间点详情
     */
    public function getDeliveryInfo($id)
    {
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeInfo = $deliveryTimeServiceModule->getDeliveryTimeInfo($id);
        if (empty($getDeliveryTimeInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        return returnData($getDeliveryTimeInfo['data']);
    }

    /**
     * @param $id
     * @return mixed
     * 删除时间点
     */
    public function delDeliveryTime($id)
    {
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeInfo = $deliveryTimeServiceModule->getDeliveryTimeInfo($id);
        if (empty($getDeliveryTimeInfo['data'])) {
            return returnData(false, -1, 'error', '暂无相关数据');
        }
        $where = [];
        $where['id'] = $id;
        $data = M("delivery_time")->where($where)->delete();
        return returnData(true);
    }

    /**
     * @param $saveData
     * @return mixed
     * 更新时间点
     */
    public function updateDeliveryTime($saveData)
    {
        $deliveryTimeServiceModule = new DeliveryTimeServiceModule();
        $getDeliveryTimeInfo = $deliveryTimeServiceModule->getDeliveryTimeInfo($saveData['id']);
        if (empty($getDeliveryTimeInfo['data'])) {
            return  returnData(false, -1, 'error', '暂无相关数据');
        }
        $deliveryTimeServiceModule->editDeliveryTimeInfo($saveData);
        return returnData(true);
    }
}