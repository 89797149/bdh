<?php

namespace Merchantapi\Action;

use Merchantapi\Model\DeliveryTimeModel;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 配送时间控制器
 */
class DeliveryTimeAction extends BaseAction
{

    /**
     * 获取配送时间分类列表
     */
    public function getTypeList()
    {
        $shopId = $this->MemberVeri()['shopId'];
        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->getTypeList($shopId);
        $this->ajaxReturn(returnData($data));
    }

    /**
     * 添加配送时间分类
     */
    public function addType()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $typeName = I("typeName");
        if (empty($typeName)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请添加分类名'));
        }
        $sort = (int)I("sort", 0);
        $number = (int)I("number", 0);

        $addData = [];
        $addData['typeName'] = $typeName;
        $addData['shopId'] = $shopId;
        $addData['sort'] = $sort;
        $addData['number'] = $number;
        $addData['addTime'] = date("Y-m-d H:i:s");

        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->addType($addData);

        if ($data) {
            $rs = returnData(true, 0, 'success', '操作成功');
        } else {
            $rs = returnData(false, -1, 'error', '操作失败');
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 获取配送时间分类详情
     */
    public function getTypeInfo()
    {
        $this->MemberVeri();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间分类'));
        }
        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->getTypeInfo($id);
        $this->ajaxReturn($data);
    }

    /**
     * 删除配送时间分类
     */
    public function deleteType()
    {
        $this->MemberVeri();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间分类'));
        }
        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->deleteType($id);
        $this->ajaxReturn($data);
    }

    /**
     * 更新配送时间分类
     */
    public function updateType()
    {
        $this->MemberVeri();
        $params = I();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间分类'));
        }

        $saveData = [];
        $saveData['sort'] = null;
        $saveData['number'] = null;
        $saveData['typeName'] = null;

        parm_filter($saveData, $params);
        $saveData['id'] = $id;

        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->updateType($saveData);

        if ($data) {
            $rs = returnData(true, 0, 'success', '操作成功');
        } else {
            $rs = returnData(false, -1, 'error', '操作失败，请确认是否有变动');
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 获取时间点列表
     */
    public function getDeliveryTimeList()
    {
        $this->MemberVeri();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间分类'));
        }
        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->getDeliveryTimeList($id);

        $rs = returnData($data);
        $this->ajaxReturn($rs);
    }

    /**
     * 添加时间点
     */
    public function addDeliveryTime()
    {
        $shopId = $this->MemberVeri()['shopId'];

        $timeStart = I("timeStart");
        $timeEnd = I("timeEnd");
        if (empty($timeStart) || empty($timeEnd)) {
            $rs = returnData(false, -1, 'error', '请添加开始时间点或结束时间点');
            $this->ajaxReturn($rs);
        }
        $sort = I("sort");
        $deliveryTimeTypeId = I("deliveryTimeTypeId");
        if (empty($deliveryTimeTypeId)) {
            $rs = returnData(false, -1, 'error', '请添加配送时间分类');
            $this->ajaxReturn($rs);
        }

        $addData = [];
        $addData["shopId"] = $shopId;
        $addData["timeStart"] = $timeStart;
        $addData["timeEnd"] = $timeEnd;
        $addData["sort"] = $sort;
        $addData["deliveryTimeTypeId"] = $deliveryTimeTypeId;
        $addData["addTime"] = date("Y-m-d H:i:s");

        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->addDeliveryTime($addData);

        if ($data) {
            $rs = returnData(true, 0, 'success', '操作成功');
        } else {
            $rs = returnData(false, -1, 'error', '操作失败');
        }
        $this->ajaxReturn($rs);
    }

    /**
     * 获取时间点详情
     */
    public function getDeliveryInfo(){
        $this->MemberVeri();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间'));
        }
        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->getDeliveryInfo($id);

        $this->ajaxReturn($data);
    }

    /**
     * 删除时间点
     */
    public function delDeliveryTime()
    {
        $this->MemberVeri();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间'));
        }
        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->delDeliveryTime($id);

        $this->ajaxReturn($data);
    }

    /**
     * 更新时间点
     */
    public function updateDeliveryTime()
    {
        $this->MemberVeri();
        $id = (int)I("id", 0);
        if (empty($id)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '请选择要操作的时间'));
        }

        $where['id'] = I("id");
        $deliveryInfo = M("delivery_time")->where($where)->find();
        if (empty($deliveryInfo)) {
            $this->ajaxReturn(returnData(false, -1, 'error', '暂无相关数据'));
        }
        $params = I();
        $saveData = [];
        $saveData["timeStart"] = null;
        $saveData["timeEnd"] = null;
        $saveData["sort"] = null;
        parm_filter($saveData, $params);
        $saveData["id"] = $id;

        $deliveryTimeModel = new DeliveryTimeModel();
        $data = $deliveryTimeModel->updateDeliveryTime($saveData);
        $this->ajaxReturn($data);
    }
}