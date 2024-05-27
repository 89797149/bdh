<?php
/**
 * 调拨单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-26
 * Time: 10:31
 */

namespace App\Modules\Allocation;


use App\Enum\Allocation\AllocationBillEnum;
use App\Enum\ExceptionCodeEnum;
use App\Models\AllocationBillGoodsModel;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Shops\ShopsModule;
use Merchantapi\Model\AllocationBillModel;
use Think\Model;

class AllocationBillModule
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
        $shopId = (int)$paramsInput['shopId'];
        $goodsId = (int)$paramsInput['goodsId'];
        $skuId = (int)$paramsInput['skuId'];
        $num = (float)$paramsInput['num'];
        $goodsModule = new GoodsModule();
        $currGoodsDetail = $goodsModule->getGoodsInfoById($goodsId, 'goodsId,goodsName,goodsSn', 2);
        if (empty($currGoodsDetail)) {
            return array();
        }
        $shopModule = new ShopsModule();
        $currShopDetail = $shopModule->getShopsInfoById($shopId, 'shopId,shopName,latitude,longitude', 2);
        if (empty($currShopDetail)) {
            return array();
        }
        $lat = $currShopDetail['latitude'];
        $lng = $currShopDetail['longitude'];
        $goodsSn = $currGoodsDetail['goodsSn'];
        $skuBarcode = '';
        $currGoodsSku = $goodsModule->getGoodsSku($goodsId, 2);
        if (!empty($skuId) && !empty($currGoodsSku)) {
            foreach ($currGoodsSku as $currSkuDetail) {
                if ($currSkuDetail['skuId'] == $skuId) {
                    $skuBarcode = $currSkuDetail['systemSpec']['skuBarcode'];
                }
            }
            if (empty($skuBarcode)) {
                return array();
            }
        }
        $model = new AllocationBillModel();
        $where = " goods.goodsFlag=1 and goods.isSale=1 and goods.goodsStatus=1 and goods.goodsSn='{$goodsSn}' ";
        $where .= " and shop.shopFlag=1 and shop.shopStatus=1 and shop.shopId != '{$shopId}'";
        $field = 'goods.goodsId,goods.goodsName';
        if (empty($skuBarcode)) {
            $field .= ",goods.goodsStock,goods.goodsImg as goodsImg";
        } else {
            $field .= ",system.skuId,system.skuGoodsStock as goodsStock,system.skuGoodsImg as goodsImg";
        }
        $field .= ',shop.shopId,shop.shopName';
        $field .= ",ROUND(6378.138*2*ASIN(SQRT(POW(SIN(($lat*PI()/180-shop.latitude*PI()/180)/2),2)+COS($lat*PI()/180)*COS(shop.latitude*PI()/180)*POW(SIN(($lng*PI()/180-shop.longitude*PI()/180)/2),2)))*1000) / 1000 AS distance ";//距离
        $sql = "select $field from __PREFIX__goods goods left join __PREFIX__shops shop on shop.shopId=goods.shopId ";
        if (!empty($skuBarcode)) {
            $where .= " and system.skuBarcode='{$skuBarcode}' and system.dataFlag=1 ";
            $sql .= " left join __PREFIX__sku_goods_system system on system.goodsId=goods.goodsId ";
        }
        $sql .= " where {$where} order by distance asc ";
        $result = $model->query($sql);
        foreach ($result as &$item) {
            if (empty($item['skuId'])) {
                $item['skuId'] = 0;
            }
            $skuIdArr[] = $item['skuId'];
            $item['skuSpecAttrStr'] = '';//sku属性值
            $item['num'] = (string)$num;//调拨数量,统一格式
        }
        unset($item);
        if (empty($skuIdArr)) {
            return (array)$result;
        }
        $skuList = $goodsModule->getSkuSystemListById(implode(',', $skuIdArr), 2);
        foreach ($result as &$item) {
            foreach ($skuList as $skuVal) {
                if ($item['skuId'] == $skuVal['skuId']) {
                    $item['skuSpecAttrTwo'] .= $skuVal['skuSpecAttrTwo'];
                }
            }
        }
        unset($item);
        return (array)$result;
    }

    /**
     * 调拨单-创建调拨单
     * @param array $paramsInput
     * -array loginInfo
     * -int goodsId 商品id
     * -int skuId skuId
     * -float num 调拨数量
     * @param object $trans
     * @return array
     */
    public function createAllocationBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $loginInfo = $paramsInput['loginInfo'];
        $goodsId = (int)$paramsInput['goodsId'];
        $skuId = (int)$paramsInput['skuId'];
        $num = (float)$paramsInput['num'];
        if ($num <= 0) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨数量必须大于0');
        }
        $goodsModule = new GoodsModule();
        $field = 'goodsId,goodsName,goodsStock,shopId,goodsImg';
        $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, $field, 2);//出库商品信息
        if (empty($goodsDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goodsDetail['skuSpecStr'] = '';
        $outShopId = $goodsDetail['shopId'];
        $inputShopId = $loginInfo['shopId'];
        if ($outShopId == $inputShopId) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请勿提交自己门店的商品');
        }
        $currGoodsStock = (float)$goodsDetail['goodsStock'];
        if (!empty($skuId)) {
            $skuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
            if (empty($skuDetail)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '规格信息有误');
            }
            $currGoodsStock = (float)$skuDetail['skuGoodsStock'];
            $goodsDetail['skuSpecStr'] = $skuDetail['skuSpecAttrTwo'];
            $goodsDetail['goodsImg'] = $skuDetail['skuGoodsImg'];
        }
        if ($currGoodsStock < $num) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "当前门店最多可调拨数量：{$currGoodsStock}");
        }
        $billData = array(
            'outShopId' => $outShopId,
            'inputShopId' => $inputShopId,
            'status' => 1,
            'creatorId' => $loginInfo['user_id'],
            'creatorName' => $loginInfo['user_username'],
        );
        $billId = $this->saveAllocationBill($billData, $dbTrans);
        if (!$billId) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "调拨单创建失败", "主单据信息创建失败");
        }
        $saveGoodsParams = array(
            'allId' => $billId,
            'goodsId' => $goodsId,
            'skuId' => $skuId,
            'num' => $num,
            'goodsName' => $goodsDetail['goodsName'],
            'goodsImg' => $goodsDetail['goodsImg'],
            'skuSpecStr' => $goodsDetail['skuSpecStr'],
        );
        $saveBillGoodsId = $this->saveAllocationBillGoods($saveGoodsParams, $dbTrans);
        if (!$saveBillGoodsId) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "调拨单创建失败", "单据商品关联失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_allocation_bill',
            'dataId' => $billId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'status',
            'fieldValue' => 1,
            'remark' => '调入方创建调拨单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "调拨单创建失败", "调拨单创建日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 调拨单-修改调拨单
     * @param array $paramsInput
     * -array loginInfo
     * -int allId 调拨单id
     * -int goodsId 商品id
     * -int skuId skuId
     * -float num 调拨数量
     * @param object $trans
     * @return array
     */
    public function updateAllocationBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $loginInfo = $paramsInput['loginInfo'];
        $allId = (int)$paramsInput['allId'];
        $goodsId = (int)$paramsInput['goodsId'];
        $skuId = (int)$paramsInput['skuId'];
        $num = (float)$paramsInput['num'];
        $billDetail = $this->getAllocationBillDetailById($allId);
        if (empty($billDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据id不合法');
        }
        if ($billDetail['inputShopId'] != $loginInfo['shopId']) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '无权限修改');
        }
        if ($billDetail['status'] > 1) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前状态不允许修改');
        }
        if ($num <= 0) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '调拨数量必须大于0');
        }
        $goodsModule = new GoodsModule();
        $field = 'goodsId,goodsName,goodsStock,shopId,goodsImg';
        $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, $field, 2);//出库商品信息
        if (empty($goodsDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
        }
        $goodsSku = $goodsModule->getGoodsSku($goodsId, 2);
        if (!empty($goodsSku) && empty($skuId)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品规格');
        }
        $goodsDetail['skuSpecStr'] = '';
        $outShopId = $goodsDetail['shopId'];
        $inputShopId = $loginInfo['shopId'];
        if ($outShopId == $inputShopId) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请勿提交自己门店的商品');
        }
        $currGoodsStock = (float)$goodsDetail['goodsStock'];
        if (!empty($skuId)) {
            $skuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
            if (empty($skuDetail)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '规格信息有误');
            }
            $currGoodsStock = (float)$skuDetail['skuGoodsStock'];
            $goodsDetail['skuSpecStr'] = $skuDetail['skuSpecAttrTwo'];
            $goodsDetail['goodsImg'] = $skuDetail['skuGoodsImg'];
        }
        if ($currGoodsStock < $num) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "当前门店最多可调拨数量：{$currGoodsStock}");
        }
        $saveGoodsParams = array(
            'id' => $billDetail['id'],
            'goodsId' => $goodsId,
            'skuId' => $skuId,
            'num' => $num,
            'goodsName' => $goodsDetail['goodsName'],
            'goodsImg' => $goodsDetail['goodsImg'],
            'skuSpecStr' => $goodsDetail['skuSpecStr'],
        );
        $saveBillGoodsId = $this->saveAllocationBillGoods($saveGoodsParams, $dbTrans);
        if (!$saveBillGoodsId) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "调拨单修改失败", "单据商品关联失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_allocation_bill',
            'dataId' => $allId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'status',
            'fieldValue' => 1,
            'remark' => '调入方修改了调拨单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "调拨单创建失败", "调拨单创建日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 调拨单据-保存
     * @param array $paramsInput
     *-wst_allocation_bill表字段
     * @param object $trans
     * @return int
     * */
    public function saveAllocationBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new AllocationBillModel();
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'billNo' => null,
            'outShopId' => null,
            'inputShopId' => null,
            'status' => null,
            'creatorId' => null,
            'creatorName' => null,
            'billPic' => null,
            'consigneeName' => null,
            'updateTime' => $datetime,
            'isDelete' => null,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = time();
            }
        }
        if (empty($paramsInput['allId'])) {
            $saveParams['createTime'] = $datetime;
            $allId = $model->add($saveParams);
            if (!$allId) {
                $dbTrans->rollback();
                return 0;
            }
            $saveParams = array(
                'allId' => $allId,
                'billNo' => 'DB' . date('Ymd') . str_pad($allId, 10, "0", STR_PAD_LEFT),
            );
            $this->saveAllocationBill($saveParams, $dbTrans);
        } else {
            $allId = $paramsInput['allId'];
            $where = array(
                'allId' => $allId
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$allId;
    }

    /**
     * 调拨单商品关联-保存
     * @param array $paramsInput
     * wst_allocation_bill_goods表字段
     * @return int
     * */
    public function saveAllocationBillGoods(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new AllocationBillGoodsModel();
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'allId' => null,
            'goodsId' => null,
            'skuId' => null,
            'num' => null,
            'goodsImg' => null,
            'goodsName' => null,
            'skuSpecStr' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (empty($paramsInput['id'])) {
            $saveParams['createTime'] = $datetime;
            $id = $model->add($saveParams);
            if (!$id) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $paramsInput['id'];
            $where = array(
                'id' => $id
            );
            $saveRes = $model->where($where)->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$id;
    }

    /**
     * 调拨单-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int type 类型(1:我的调入 2:我的调出 3:已完成的调拨)
     * -string billNo 单号
     * -string goodsKeywords 商品关键字
     * -int status 调拨状态【1:调出方待确认|2:等待调入方收货|3:调出方已交货】
     * -date billDateStart 制单日期-开始日期
     * -date billDateEnd 制单日期-结束日期
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getAllocationBillList(array $paramsInput)
    {
        $model = new AllocationBillGoodsModel();
        $type = $paramsInput['type'];
        $shopId = $paramsInput['shopId'];
        if (empty($type)) {
            return array();
        }
        $where = "bill.isDelete=0 ";
        if ($type == 1) {
            $where .= " and bill.inputShopId={$shopId} ";
        } elseif ($type == 2) {
            $where .= " and bill.outShopId={$shopId} ";
        } else {
            $where .= " and ((bill.outShopId={$shopId} and status=3) or (bill.inputShopId={$shopId} and status=3)) ";
        }
        if (!empty($paramsInput['billNo'])) {
            $where .= " and bill.billNo like '%{$paramsInput['billNo']}%' ";
        }
        if (!empty($paramsInput['goodsKeywords'])) {
            $goodsKeywords = $paramsInput['goodsKeywords'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goodsKeywords, $match)) {
                $goodsKeywords = strtoupper($goodsKeywords);
            }
            $where .= " and (b_goods.goodsName like '%{$paramsInput['goodsKeywords']}%' or goods.py_code like '%{$goodsKeywords}%' or goods.py_initials like '%{$goodsKeywords}%' or goods.goodsSn like '%{$paramsInput['goodsKeywords']}%') ";
        }
        if (isset($paramsInput['status'])) {
            if (is_numeric($paramsInput['status'])) {
                $where .= " and bill.status={$paramsInput['status']}";
            }
        }
        if (!empty($paramsInput['billDateStart'])) {
            $where .= " and bill.createTime >= '{$paramsInput['billDateStart']} 00:00:00' ";
        }
        if (!empty($paramsInput['billDateEnd'])) {
            $where .= " and bill.createTime <= '{$paramsInput['billDateEnd']} 23:59:59' ";
        }

        $field = 'bill.allId,bill.billNo,bill.outShopId,bill.inputShopId,bill.status,bill.consigneeName,bill.billPic,bill.createTime';
        $field .= ',b_goods.num,b_goods.goodsId,b_goods.skuId,b_goods.goodsName,b_goods.goodsImg,b_goods.skuSpecStr';
        $sql = $model
            ->alias('b_goods')
            ->join('left join wst_goods goods on goods.goodsId=b_goods.goodsId')
            ->join('left join wst_allocation_bill bill on bill.allId=b_goods.allId')
            ->where($where)
            ->field($field)
            ->order('bill.createTime desc')
            ->buildSql();
        $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        if (!empty($result['root'])) {
            $list = $result['root'];
            $shopModule = new ShopsModule();
            $allocationEnum = new AllocationBillEnum();
            foreach ($list as &$item) {
                $inputShopDetail = $shopModule->getShopsInfoById($item['inputShopId'], 'shopName', 2);
                $item['inputShopName'] = (string)$inputShopDetail['shopName'];
                $outShopDetail = $shopModule->getShopsInfoById($item['outShopId'], 'shopName', 2);
                $item['outShopName'] = (string)$outShopDetail['shopName'];
                $item['statusName'] = $allocationEnum::getStatusName()[$item['status']];
            }
            unset($item);
            $result['root'] = $list;
        }
        return $result;
    }

    /**
     * 调拨单-详情
     * @param int $allId 调拨单id
     * @return array
     * */
    public function getAllocationBillDetailById(int $allId)
    {
        $model = new AllocationBillGoodsModel();
        $where = "bill.isDelete=0 and bill.allId={$allId}";
        $field = 'bill.allId,bill.billNo,bill.outShopId,bill.inputShopId,bill.status,bill.consigneeName,bill.billPic,bill.createTime';
        $field .= ',b_goods.id,b_goods.num,b_goods.goodsId,b_goods.skuId,b_goods.goodsName,b_goods.goodsImg,b_goods.skuSpecStr';
        $result = $model
            ->alias('b_goods')
            ->join('left join wst_goods goods on goods.goodsId=b_goods.goodsId')
            ->join('left join wst_allocation_bill bill on bill.allId=b_goods.allId')
            ->where($where)
            ->field($field)
            ->find();
        if (empty($result)) {
            return array();
        }
        $shopModule = new ShopsModule();
        $allocationEnum = new AllocationBillEnum();
        $inputShopDetail = $shopModule->getShopsInfoById($result['inputShopId'], 'shopName', 2);
        $result['inputShopName'] = (string)$inputShopDetail['shopName'];
        $outShopDetail = $shopModule->getShopsInfoById($result['outShopId'], 'shopName', 2);
        $result['outShopName'] = (string)$outShopDetail['shopName'];
        $result['statusName'] = $allocationEnum::getStatusName()[$result['status']];
        return $result;
    }

    /**
     * 调拨单-操作日志
     * @param int $allId 调拨单id
     * @return array
     * */
    public function getAllocationBillLog(int $allId)
    {
        $logParams = array(
            'tableName' => 'wst_allocation_bill',
            'dataId' => $allId
        );
        $logData = (new TableActionLogModule())->getLogListByParams($logParams);
        $newLogData = array();
        foreach ($logData as $logDetail) {
            $newLogData[] = array(
                'logId' => $logDetail['logId'],
                'actionUserName' => $logDetail['actionUserName'],
                'remark' => $logDetail['remark'],
                'createTime' => $logDetail['createTime'],
            );
        }
        return $newLogData;
    }

    /**
     * 调拨单-调出方确认调拨
     * @param int $allId 调拨单id
     * @param array $loginInfo 当前登陆者信息
     * @param object $trans
     * @return array
     * */
    public function confirmAllocation(int $allId, array $loginInfo, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $billDetail = $this->getAllocationBillDetailById($allId);
        if ($billDetail['status'] != 1) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前状态不能执行确认调拨操作');
        }
        if ($billDetail['outShopId'] != $loginInfo['shopId']) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非调出方不能执行确认调拨操作');
        }
        $allocationBillData = array(
            'allId' => $allId,
            'status' => 2,
        );
        $saveAllocationRes = $this->saveAllocationBill($allocationBillData, $dbTrans);
        if (!$saveAllocationRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '调拨单更新失败');
        }
        $goodsModule = new GoodsModule();
        $goodsId = $billDetail['goodsId'];
        $skuId = $billDetail['skuId'];
        $num = (float)$billDetail['num'];
        $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, 'goodsId,goodsName,goodsStock', 2);
        $currStock = $goodsDetail['goodsStock'];
        if (!empty($skuId)) {
            $skuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
            $currStock = $skuDetail['skuGoodsStock'];
        }
        if ((float)$currStock < $num) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品库房库存不足');
        }
        $decGoodsStockRes = $goodsModule->deductionGoodsStock($goodsId, $skuId, $num, 1, 1, $dbTrans);
        if ($decGoodsStockRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品库存更新失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_allocation_bill',
            'dataId' => $allId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'status',
            'fieldValue' => 2,
            'remark' => '调出方已确认调拨',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "调拨单日志记录失败");
        }
        $ExData = array(
            'pagetype' => 3,
            'shopId' => $loginInfo['shopId'],
            'user_id' => $loginInfo['user_id'],
            'user_name' => $loginInfo['user_username'],
            'remark' => '',
            'relation_order_number' => $billDetail['billNo'],
            'relation_order_id' => $allId,
            'goods_data' => array(
                array(
                    'goods_id' => $billDetail['goodsId'],
                    'sku_id' => $billDetail['skuId'],
                    'nums' => $billDetail['num'],
                    'actual_delivery_quantity' => $billDetail['num'],
                )
            ),
        );
        $addExRes = (new ExWarehouseOrderModule())->addExWarehouseOrder($ExData, $dbTrans);
        if (!$addExRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "出库单创建失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
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
    public function deliveryAllocation(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $allId = $paramsInput['allId'];
        $billPic = $paramsInput['billPic'];
        $consigneeName = $paramsInput['consigneeName'];
        $loginInfo = $paramsInput['loginInfo'];

        $billDetail = $this->getAllocationBillDetailById($allId);
        if ($billDetail['status'] != 2) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前状态不能执行发货操作');
        }
        if ($billDetail['outShopId'] != $loginInfo['shopId']) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非调出方不能执行发货操作');
        }
        $saveBillData = array(
            'allId' => $allId,
            'status' => 3,
            'billPic' => $billPic,
            'consigneeName' => $consigneeName,
        );
        $saveRes = $this->saveAllocationBill($saveBillData, $dbTrans);
        if (!$saveRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '单据信息更新失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_allocation_bill',
            'dataId' => $allId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'status',
            'fieldValue' => 3,
            'remark' => '调出方已交货',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "调拨单日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }
}