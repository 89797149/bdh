<?php
/**
 * 调拨单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-26
 * Time: 10:28
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Modules\Allocation\AllocationBillModule;
use App\Modules\Goods\GoodsModule;
use Think\Model;

class AllocationBillModel extends BaseModel
{
    /**
     * 调拨-获取可调拨的门店商品
     * @param array $paramsInput
     * -int shopId 门店
     * -int goodsId 商品id
     * -int skuId 规格id
     * -float num 调拨数量
     * @return array
     * */
    public function getAllocationGoodsList(array $paramsInput)
    {
        $goodsId = (int)$paramsInput['goodsId'];
        $skuId = (int)$paramsInput['skuId'];
        $num = (float)$paramsInput['num'];
        if ($num <= 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨数量必须大于0');
        }
        $goodsModule = new GoodsModule();
        $currGoodsSku = $goodsModule->getGoodsSku($goodsId, 2);
        if (!empty($currGoodsSku) && empty($skuId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择规格');
        }
        $module = new AllocationBillModule();
        $result = $module->getAllocationGoodsList($paramsInput);
        return returnData($result);
    }

    /**
     * 调拨单-申请调拨
     * @param array $paramsInput
     * -array loginInfo
     * -int goodsId 商品id
     * -int skuId skuId
     * -float num 调拨数量
     * @return array
     * */
    public function addAllocationBill(array $paramsInput)
    {
        $module = new AllocationBillModule();
        $result = $module->createAllocationBill($paramsInput);
        return $result;
    }

    /**
     * 调拨单-修改
     * @param array $paramsInput
     * -array loginInfo
     * -int allId 调拨单id
     * -int goodsId 商品id
     * -int skuId skuId
     * -float num 调拨数量
     * @return array
     * */
    public function updateAllocationBill(array $paramsInput)
    {
        $module = new AllocationBillModule();
        $result = $module->updateAllocationBill($paramsInput);
        return $result;
    }

    /**
     * 调拨单-列表
     * @param array $paramsInput
     * @return array
     * */
    public function getAllocationBillList(array $paramsInput)
    {
        $module = new AllocationBillModule();
        $result = $module->getAllocationBillList($paramsInput);
        return $result;
    }

    /**
     * 调拨单-详情
     * @param int $allId 调拨单id
     * @return array
     * */
    public function getAllocationBillDetail(int $allId)
    {
        $module = new AllocationBillModule();
        $result = $module->getAllocationBillDetailById($allId);
        $result['log_data'] = $module->getAllocationBillLog($allId);
        return $result;
    }

    /**
     * 调拨单-调出方确认调拨
     * @param int $allId 调拨单id
     * @param array $loginInfo 登陆者信息
     * @return array
     * */
    public function confirmAllocation(int $allId, array $loginInfo)
    {
        $module = new AllocationBillModule();
        $result = $module->confirmAllocation($allId, $loginInfo);
        return $result;
    }

    /**
     * 调拨单-调出方发货
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int allId 调拨单id
     * -string billPic 单据照片
     * -string consigneeName 接货人姓名
     * @return array
     * */
    public function deliveryAllocation(array $paramsInput)
    {
        $module = new AllocationBillModule();
        $result = $module->deliveryAllocation($paramsInput);
        return $result;
    }

    /**
     * 调拨单-调入方删除单据
     * @param array $allIdArr 调拨单id
     * @param int $shopId 门店id
     * @return array
     * */
    public function delAllocation(array $allIdArr, int $shopId)
    {
        $module = new AllocationBillModule();
        foreach ($allIdArr as $allId) {
            $billDetail = $module->getAllocationBillDetailById($allId);
            $billNo = $billDetail['billNo'];
            if ($billDetail['status'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "只有调出方待确认的单据才允许删除");
            }
            if ($billDetail['inputShopId'] != $shopId) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "不是单据{$billNo}的创建者，没有权限删除");
            }
        }
        $model = new \App\Models\AllocationBillModel();
        $where = array(
            'allId' => array('in', implode(',', $allIdArr)),
        );
        $saveParams = array(
            'isDelete' => 1,
            'deleteTime' => time(),
        );
        $saveRes = $model->where($where)->save($saveParams);
        if (!$saveRes) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        return returnData(true);
    }
}