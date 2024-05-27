<?php
/**
 * 采购单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-20
 * Time: 18:32
 */

namespace App\Modules\PurchaseBill;


use App\Enum\ExceptionCodeEnum;
use App\Enum\PurchaseBill\PurchaseBillEnum;
use App\Models\PurchaseBillGoodsModel;
use App\Models\PurchaseBillModel;
use App\Models\PurchaseGoodsModel;
use App\Models\PurchaseReturnBillGoodsModel;
use App\Models\PurchaseReturnBillModel;
use App\Models\WarehousingBillModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\ShopStaffMember\ShopStaffMemberModule;
use App\Modules\Supplier\SupplierModule;
use App\Modules\WarehousingBill\WarehousingBillModule;
use Think\Model;

class PurchaseBillModule
{
    /**
     * 采购单-创建
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int billFrom 单据来源(1:手动创建采购单 2:订单汇总生产采购单 3:预采购生成采购单 4:现场采购订单 5:采购任务生成采购单 6:采购任务生成采购单(联营))
     * -int purchaserId 采购员id
     * -int supplierId 供应商id
     * -date plannedDeliveryDate 计划交货日期
     * -string billRemark 单据备注
     * -array goods_data 商品信息
     * --int goosdId 商品id
     * --int skuId 商品规格id
     * --float purchaseTotalNum 采购数量小计
     * --float purchasePriceTotal 采购金额小计
     * --string goodsRemark 商品备注
     * @param object $trans
     * @return array
     * */
    public function createPurchaseBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if (empty($paramsInput['loginInfo'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少登陆者信息');
        }
        if (!in_array($paramsInput['billFrom'], array(1, 2, 3, 4, 5, 6))) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据来源异常');
        }
        if (empty($paramsInput['purchaserId']) && empty($paramsInput['supplierId'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择单据类型');
        }
        $billType = 0;
        if (!empty($paramsInput['purchaserId'])) {
            $billType = 1;
        } elseif (!empty($paramsInput['supplierId'])) {
            $billType = 2;
        }
        if (empty($paramsInput['plannedDeliveryDate'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择计划交货日期');
        }
        if (empty($paramsInput['goods_data'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品');
        }
        $loginInfo = $paramsInput['loginInfo'];
        $goodsData = $paramsInput['goods_data'];
        $purchaseGoods = array();//采购商品
        $goodsModule = new GoodsModule();
        $datetime = date('Y-m-d H:i:s');
        foreach ($goodsData as $goodsDetail) {
            $goodsId = (int)$goodsDetail['goodsId'];
            $skuId = (int)$goodsDetail['skuId'];
            $purchaseTotalNum = (float)$goodsDetail['purchaseTotalNum'];//采购数量小计
            $purchasePriceTotal = (float)$goodsDetail['purchasePriceTotal'];//采购金额小计
            $goodsField = 'goodsId,goodsName,goodsSpec,goodsImg,unit';
            $goodsInfo = $goodsModule->getGoodsInfoById($goodsId, $goodsField, 2);
            if (empty($goodsInfo)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
            }
            $goodsName = $goodsInfo['goodsName'];
            if ($purchaseTotalNum <= 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}采购数量必须大于0");
            }
            if ($purchasePriceTotal < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}采购金额小计有误");
            }
            $uniqueTag = $goodsId . '@' . $skuId;//商品唯一标识
            if (isset($purchaseGoods[$uniqueTag])) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}存在重复数据，请核对表单数据");
            }
            $purchaseGoodsDetail = array(
                'goodsId' => $goodsId,
                'skuId' => $skuId,
                'goodsName' => $goodsName,
                'goodsImg' => $goodsInfo['goodsImg'],
                'describe' => (string)$goodsInfo['goodsSpec'],
                'unitName' => (string)$goodsInfo['unit'],
                'skuSpecStr' => '',
                'purchasePrice' => bc_math($purchasePriceTotal, $purchaseTotalNum, 'bcdiv', 2),
                'purchasePriceTotal' => $purchasePriceTotal,
                'purchaseTotalNum' => $purchaseTotalNum,
                'goodsRemark' => $goodsDetail['goodsRemark'],
                'createTime' => $datetime,
                'updateTime' => $datetime,
            );
            if ($skuId > 0) {
                $skuInfo = $goodsModule->getSkuSystemInfoById($skuId, 2);
                if (empty($skuInfo)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}规格信息有误");
                }
                $purchaseGoodsDetail['goodsImg'] = (string)$skuInfo['skuGoodsImg'];
                $purchaseGoodsDetail['unitName'] = (string)$skuInfo['unit'];
                $purchaseGoodsDetail['skuSpecStr'] = (string)$skuInfo['skuSpecAttrTwo'];
            }
            $purchaseGoods[$uniqueTag] = $purchaseGoodsDetail;
        }
        $purchaseGoods = array_values($purchaseGoods);
        $billData = array(
            'shopId' => $loginInfo['shopId'],
            'billType' => $billType,
            'billFrom' => $paramsInput['billFrom'],
            'purchaserId' => $paramsInput['purchaserId'],
            'supplierId' => $paramsInput['supplierId'],
            'creatorId' => $loginInfo['user_id'],
            'creatorName' => $loginInfo['user_username'],
            'plannedDeliveryDate' => $paramsInput['plannedDeliveryDate'],
            'billRemark' => $paramsInput['billRemark'],
            'billAmount' => array_sum(array_column($purchaseGoods, 'purchasePriceTotal')),
        );
        $billId = $this->savePurchaseBill($billData, $dbTrans);
        if (empty($billId)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "添加失败", "单据创建失败");
        }
        foreach ($purchaseGoods as &$item) {
            $item['purchaseId'] = $billId;
        }
        unset($item);
        $purchaseGoodsModel = new PurchaseBillGoodsModel();
        $addGoodsRes = $purchaseGoodsModel->addAll($purchaseGoods);
        if (!$addGoodsRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "添加失败", "单据商品添加失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_bill',
            'dataId' => $billId,
            'actionUserId' => $billData['creatorId'],
            'actionUserName' => $billData['creatorName'],
            'fieldName' => 'purchaseStatus',
            'fieldValue' => 0,
            'remark' => '已创建采购单，等待收货',
        ];
        if (!empty($billData['supplierId'])) {
            $logParams['remark'] = '已创建采购单，等待供应商确认';
        }
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "添加失败", "单据日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 采购单-修改
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int purchaseId 采购单id
     * -int purchaserId 采购员id
     * -int supplierId 供应商id
     * -date plannedDeliveryDate 计划交货日期
     * -string billRemark 单据备注
     * -array goods_data 商品信息
     * --int id 采购商品唯一标识id,有值为修改,无值为新增
     * --int goosdId 商品id
     * --int skuId 商品规格id
     * --float purchaseTotalNum 采购数量小计
     * --float purchasePriceTotal 采购金额小计
     * --string goodsRemark 商品备注
     * @param object $trans
     * @return array
     * */
    public function updatePurchaseBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $purchaseDetail = $this->getPurchaseDetailById($paramsInput['purchaseId']);
        if (empty($purchaseDetail)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        if ($purchaseDetail['purchaseStatus'] != 0) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '非待采购状态下的单据禁止修改');
        }
        if (empty($paramsInput['loginInfo'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少登陆者信息');
        }
        if (!in_array($paramsInput['billFrom'], array(1, 2, 3, 4, 5, 6))) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据来源异常');
        }
        if (empty($paramsInput['purchaserId']) && empty($paramsInput['supplierId'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择单据类型');
        }
        $billType = 0;
        if (!empty($paramsInput['purchaserId'])) {
            $billType = 1;
        } elseif (!empty($paramsInput['supplierId'])) {
            $billType = 2;
        }
        if (empty($paramsInput['plannedDeliveryDate'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择计划交货日期');
        }
        if (empty($paramsInput['goods_data'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品');
        }
        $loginInfo = $paramsInput['loginInfo'];
        $goodsData = $paramsInput['goods_data'];
        $purchaseGoods = array();//采购商品
        $goodsModule = new GoodsModule();
        $datetime = date('Y-m-d H:i:s');
        $addGoodsData = array();
        $updateGoodsData = array();
        foreach ($goodsData as $goodsDetail) {
            $id = 0;
            if (!empty($goodsDetail['id'])) {
                $id = (int)$goodsDetail['id'];
                $purchaseGoodsDetail = $this->getPurchaseGoodsDetailById($id);
                if (empty($purchaseGoodsDetail)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "采购商品信息有误");
                }
            }
            $goodsId = (int)$goodsDetail['goodsId'];
            $skuId = (int)$goodsDetail['skuId'];
            $purchaseTotalNum = (float)$goodsDetail['purchaseTotalNum'];//采购数量小计
            $purchasePriceTotal = (float)$goodsDetail['purchasePriceTotal'];//采购金额小计
            $goodsField = 'goodsId,goodsName,goodsSpec,goodsImg,unit';
            $goodsInfo = $goodsModule->getGoodsInfoById($goodsId, $goodsField, 2);
            if (empty($goodsInfo)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
            }
            $goodsName = $goodsInfo['goodsName'];
            if ($purchaseTotalNum <= 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}采购数量必须大于0");
            }
            if ($purchasePriceTotal < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}采购金额小计有误");
            }
            $uniqueTag = $goodsId . '@' . $skuId;//商品唯一标识
            if (isset($purchaseGoods[$uniqueTag])) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}存在重复数据，请核对表单数据");
            }
            $purchaseGoodsDetail = array(
                'purchaseId' => $paramsInput['purchaseId'],
                'goodsId' => $goodsId,
                'skuId' => $skuId,
                'goodsName' => $goodsName,
                'goodsImg' => $goodsInfo['goodsImg'],
                'describe' => (string)$goodsInfo['goodsSpec'],
                'unitName' => (string)$goodsInfo['unit'],
                'skuSpecStr' => '',
                'purchasePrice' => bc_math($purchasePriceTotal, $purchaseTotalNum, 'bcdiv', 2),
                'purchasePriceTotal' => $purchasePriceTotal,
                'purchaseTotalNum' => $purchaseTotalNum,
                'goodsRemark' => $goodsDetail['goodsRemark'],
                'updateTime' => $datetime,
            );
            if ($skuId > 0) {
                $skuInfo = $goodsModule->getSkuSystemInfoById($skuId, 2);
                if (empty($skuInfo)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}规格信息有误");
                }
                $purchaseGoodsDetail['goodsImg'] = (string)$skuInfo['skuGoodsImg'];
                $purchaseGoodsDetail['unitName'] = (string)$skuInfo['unit'];
                $purchaseGoodsDetail['skuSpecStr'] = (string)$skuInfo['skuSpecAttrTwo'];
            }
            if ($id > 0) {
                $purchaseGoodsDetail['id'] = $id;
                $updateGoodsData[] = $purchaseGoodsDetail;
            } else {
                $purchaseGoodsDetail['createTime'] = $datetime;
                $addGoodsData[] = $purchaseGoodsDetail;
            }
            $purchaseGoods[$uniqueTag] = $purchaseGoodsDetail;
        }
        $purchaseGoods = array_values($purchaseGoods);
        $existPurchaseGoodsList = $this->getPurchaseGoodsList($paramsInput['purchaseId']);
        $existPurchaseRelationId = array_column($existPurchaseGoodsList, 'id');
        $nowPurchaseRelationId = array_column($purchaseGoods, 'id');
        $diffRelationId = array_diff($existPurchaseRelationId, $nowPurchaseRelationId);
        if (!empty($diffRelationId)) {
            foreach ($diffRelationId as $id) {
                if (empty($id)) {
                    continue;
                }
                $delRes = $this->delPurchaseBillGoodsById($id, $dbTrans);
                if (!$delRes) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "单据商品修改失败");
                }
            }
        }
        $billData = array(
            'purchaseId' => $paramsInput['purchaseId'],
            'billType' => $billType,
            'purchaserId' => $paramsInput['purchaserId'],
            'supplierId' => $paramsInput['supplierId'],
            'plannedDeliveryDate' => $paramsInput['plannedDeliveryDate'],
            'billRemark' => $paramsInput['billRemark'],
            'billAmount' => array_sum(array_column($purchaseGoods, 'purchasePriceTotal')),
        );
        $billId = $this->savePurchaseBill($billData, $dbTrans);
        if (empty($billId)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "单据修改失败");
        }
        $model = new PurchaseBillGoodsModel();
        if (!empty($addGoodsData)) {
            $addRes = $model->addAll($addGoodsData);
            if (!$addRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "单据商品更新失败");
            }
        }
        if (!empty($updateGoodsData)) {
            $updateRes = $model->saveAll($updateGoodsData, 'wst_purchase_bill_goods', 'id');
            if ($updateRes === false) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "单据商品更新失败");
            }
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_bill',
            'dataId' => $billId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'purchaseStatus',
            'fieldValue' => $purchaseDetail['purchaseStatus'],
            'remark' => '修改了单据信息',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "添加失败", "单据日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 采购单-详情-id查找
     * @param int $purchaseId 采购单id
     * @param string $field 表字段
     * @return array
     * */
    public function getPurchaseDetailById(int $purchaseId, $field = '*')
    {
        $model = new PurchaseBillModel();
        $where = array(
            'purchaseId' => $purchaseId,
            'isDelete' => 0
        );
        $result = $model->where($where)->field($field)->find();
        return (array)$result;
    }

    /**
     * 采购单商品-删除
     * @param int|array $id 采购商品关联唯一标识id
     * @param object $trans
     * @return bool
     * */
    public function delPurchaseBillGoodsById($id, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if (!is_array($id)) {
            $id = array($id);
        }
        $idStr = implode(',', $id);
        $model = new PurchaseBillGoodsModel();
        $where = array(
            'id' => array('in', $idStr)
        );
        $delRes = $model->where($where)->delete();
        if (!$delRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购单商品-列表
     * @param int $purchaseId 采购单id
     * @param string $field 表字段
     * @param string $keywords 商品关键字
     * @return array
     * */
    public function getPurchaseGoodsList(int $purchaseId, $field = 'relation.*', $keywords = '')
    {
        $model = new PurchaseBillGoodsModel();
        $where = " relation.purchaseId={$purchaseId} ";
        if (!empty($keywords)) {
            $searchStr = $keywords;
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $searchStr, $match)) {
                $searchStr = strtoupper($searchStr);
            }
            $where .= " and (goods.goodsName like '%{$keywords}%' or goods.py_code like '%{$searchStr}%' or goods.py_initials like '%{$searchStr}%' or goods.goodsSn like '%{$keywords}%' ) ";
        }
        $result = $model
            ->alias('relation')
            ->join("left join wst_goods goods on goods.goodsId=relation.goodsId")
            ->where($where)
            ->field($field)
            ->select();
        $purEnum = new PurchaseBillEnum();
        foreach ($result as $key => &$item) {
            $key += 1;
            $item['keyNum'] = $key;
            $item['purchaseStatusName'] = $purEnum->getPurchaseStatus($item['purchaseStatus']);
            if (isset($item['purchaseOkNum']) && isset($item['purchaseTotalNum'])) {
                $item['purchaseNoNum'] = bc_math($item['purchaseTotalNum'], $item['purchaseOkNum'], 'bcsub', 3);//未收货数量
                $item["deliveryAmount"] = bc_math($item['purchaseOkNum'], $item['purchasePrice'], 'bcmul', 2);//收货金额
            }
        }
        unset($item);
        return (array)$result;
    }

    /**
     * 采购单商品-详情-id查找
     * @param int $id 采购商品唯一标识id
     * @param string $field 表字段
     * @return array
     * */
    public function getPurchaseGoodsDetailById(int $id, $field = '*')
    {
        $model = new PurchaseBillGoodsModel();
        $where = array(
            'id' => $id
        );
        $result = $model->where($where)->field($field)->find();
        return (array)$result;
    }

    /**
     * 采购单-保存单据
     * @param array $paramsInput
     * -wst_purchase_bill表字段
     * @param object $trans
     * @return int
     * */
    public function savePurchaseBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'shopId' => null,
            'billNo' => null,
            'billType' => null,
            'billFrom' => null,
            'purchaserId' => null,
            'supplierId' => null,
            'supplierConfirm' => null,
            'purchaseStatus' => null,
            'creatorId' => null,
            'creatorName' => null,
            'plannedDeliveryDate' => null,
            'billRemark' => null,
            'billAmount' => null,
            'deliveryAmount' => null,
            'updateTime' => $datetime,
            'isDelete' => null,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($paramsInput['isDelete'])) {
            if ($paramsInput['isDelete'] == 1) {
                $saveParams['deleteTime'] = time();
            }
        }
        $model = new PurchaseBillModel();
        if (empty($paramsInput['purchaseId'])) {
            $saveParams['createTime'] = $datetime;
            $purchaseId = $model->add($saveParams);
            if (!$purchaseId) {
                $dbTrans->rollback();
                return 0;
            }
            $billNo = 'CG' . date('Ymd') . str_pad($purchaseId, 10, "0", STR_PAD_LEFT);
            $saveParams = array(
                'purchaseId' => $purchaseId,
                'billNo' => $billNo,
            );
            $this->savePurchaseBill($saveParams, $trans);
        } else {
            $purchaseId = $paramsInput['purchaseId'];
            $where = array(
                'purchaseId' => $purchaseId
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
        return (int)$purchaseId;
    }

    /**
     * 采购单-商品-保存
     * @param array $paransInput
     * @param object $trans
     * @return int
     * */
    public function savePurchaseBillGoods(array $paransInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $datetime = date('Y-m-d H:i:s');
        $model = new PurchaseBillGoodsModel();
        $saveParams = array(
            'purchaseId' => null,
            'goodsId' => null,
            'skuId' => null,
            'goodsName' => null,
            'goodsImg' => null,
            'describe' => null,
            'unitName' => null,
            'skuSpecStr' => null,
            'purchaseStatus' => null,
            'purchasePrice' => null,
            'purchasePriceTotal' => null,
            'purchaseTotalNum' => null,
            'purchaseOkNum' => null,
            'warehousePrice' => null,
            'goodsRemark' => null,
            'returnGoodsStatus' => null,
            'returnOKGoodsNum' => null,
            'warehouseOkNum' => null,
            'updateTime' => null,
        );
        parm_filter($saveParams, $paransInput);
        if (empty($paransInput['id'])) {
            $saveParams['createTime'] = $datetime;
            $id = $model->add($saveParams);
            if (!$id) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $paransInput['id'];
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
     * 采购-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string billNo 单号
     * -string goodsKeywords 商品关键字
     * -int billType 单据类型(1:市场自采 2:供应商直供)
     * -int purchaserId 采购员id
     * -int supplierId 供应商id
     * -date createDateStart 创建日期区间-开始日期
     * -date createDateEnd 创建日期区间-结束日期
     * -date plannedDeliveryDateStart 计划交货日期区间-开始日期
     * -date plannedDeliveryDateEnd 计划交货日期区间-结束日期
     * int purchaseStatus 采购状态(-1:关闭 0:待采购 1:部分收货 2:全部收货)
     * int supplierConfirm 供货状态(0:未确认 1:已确认)
     * int billFrom 单据来源(1:手动创建采购单 2:订单汇总生产采购单 3:预采购生成采购单 4:现场采购订单 5:采购任务生成采购单 6:采购任务生成采购单(联营))
     * int export 是否导出(0:否 1:是)
     * int page 页码
     * int pageSize 分页条数
     * @return array
     * */
    public function getPurchaseBillList(array $paramsInput)
    {
        $where = " bill.isDelete=0 and bill.shopId={$paramsInput['shopId']}";
        if (!empty($paramsInput['billNo'])) {
            $where .= " and bill.billNo like '%{$paramsInput['billNo']}%' ";
        }
        if (!empty($paramsInput['goodsKeywords'])) {
            $goodsKeywords = $paramsInput['goodsKeywords'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goodsKeywords, $match)) {
                $goodsKeywords = strtoupper($goodsKeywords);
            }
            $where .= " and (pur_goods.goodsName like '%{$paramsInput['goodsKeywords']}%' or goods.py_code like '%{$goodsKeywords}%' or goods.py_initials like '%{$goodsKeywords}%' or goods.goodsSn like '%{$paramsInput['goodsKeywords']}%') ";
        }
        if (!empty($paramsInput['billType']) && is_numeric($paramsInput['billType'])) {
            $where .= " and bill.billType = {$paramsInput['billType']} ";
        }
        if (!empty($paramsInput['purchaserId']) && is_numeric($paramsInput['purchaserId'])) {
            $where .= " and bill.purchaserId = {$paramsInput['purchaserId']} ";
        }
        if (!empty($paramsInput['supplierId']) && is_numeric($paramsInput['supplierId'])) {
            $where .= " and bill.supplierId = {$paramsInput['supplierId']} ";
        }
        if (!empty($paramsInput['createDateStart'])) {
            $paramsInput['createDateStart'] .= ' 00:00:00';
            $where .= " and bill.createTime >= '{$paramsInput['createDateStart']}'";
        }
        if (!empty($paramsInput['createDateEnd'])) {
            $paramsInput['createDateStart'] .= ' 23:59:59';
            $where .= " and bill.createTime <= '{$paramsInput['createDateEnd']}'";
        }
        if (!empty($paramsInput['plannedDeliveryDateStart'])) {
            $where .= " and bill.plannedDeliveryDate >= '{$paramsInput['plannedDeliveryDateStart']}'";
        }
        if (!empty($paramsInput['plannedDeliveryDateEnd'])) {
            $where .= " and bill.plannedDeliveryDate <= '{$paramsInput['plannedDeliveryDateEnd']}'";
        }
        if (is_numeric($paramsInput['purchaseStatus'])) {
            $where .= " and bill.purchaseStatus={$paramsInput['purchaseStatus']} ";
        }
        if (is_numeric($paramsInput['supplierConfirm'])) {
            $where .= " and bill.supplierConfirm={$paramsInput['supplierConfirm']} ";
        }
        if (is_numeric($paramsInput['billFrom'])) {
            $where .= " and bill.billFrom={$paramsInput['billFrom']} ";
        }
        $model = new PurchaseBillGoodsModel();
        if ($paramsInput['export'] != 1) {
            $field = 'bill.purchaseId,bill.billNo,bill.billType,bill.billFrom,bill.supplierConfirm,bill.purchaseStatus,bill.creatorName,bill.plannedDeliveryDate,bill.billRemark,bill.billAmount,bill.deliveryAmount,bill.createTime,bill.printNum';
            $sql = $model
                ->alias('pur_goods')
                ->join("left join wst_purchase_bill bill on pur_goods.purchaseId=bill.purchaseId")
                ->join("left join wst_goods goods on goods.goodsId=pur_goods.goodsId")
                ->where($where)
                ->field($field)
                ->order("bill.createTime desc")
                ->group("bill.purchaseId")
                ->buildSql();
            $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
            $list = $result['root'];
        } else {
            $field = 'bill.purchaseId,bill.billNo,bill.billType,bill.billFrom,bill.plannedDeliveryDate,bill.billRemark,bill.billAmount,bill.supplierConfirm,bill.deliveryAmount,bill.createTime,bill.creatorName,bill.printNum';
            $field .= ',pur_goods.goodsName,pur_goods.unitName,pur_goods.describe,pur_goods.skuSpecStr,pur_goods.purchaseStatus,pur_goods.purchasePrice,pur_goods.purchasePriceTotal,pur_goods.purchaseTotalNum,purchaseOkNum,pur_goods.goodsRemark';
            $sql = $model
                ->alias('pur_goods')
                ->join("left join wst_purchase_bill bill on pur_goods.purchaseId=bill.purchaseId")
                ->join("left join wst_goods goods on goods.goodsId=pur_goods.goodsId")
                ->where($where)
                ->field($field)
                ->order("bill.createTime desc")
                ->buildSql();
            $list = $model->query($sql);
        }
        $purEnum = new PurchaseBillEnum();
        foreach ($list as &$item) {
            $item['purchaseStatusName'] = $purEnum->getPurchaseStatus($item['purchaseStatus']);
            $item['supplierConfirmName'] = $purEnum->getSupplierConfirm($item['supplierConfirm']);
            $item['billTypeName'] = $purEnum->getBillType($item['billType']);
            $item['billFromName'] = $purEnum->getBillFrom($item['billFrom']);
            $item['purchaserOrSupplier'] = $this->getPurchaserOrSupplier($item['purchaseId']);
        }
        unset($item);
        if ($paramsInput['export'] == 1) {
            return $list;
        }
        $result['root'] = $list;
        return $result;
    }

    /**
     * 获取单据采购员/供应商
     * @param int $purchaseId 采购单id
     * @return string
     * */
    public function getPurchaserOrSupplier(int $purchaseId)
    {
        $str = '';
        $billDetail = $this->getPurchaseDetailById($purchaseId, 'purchaserId,supplierId');
        if (!empty($billDetail['purchaserId'])) {
            $purchaserDetial = (new ShopStaffMemberModule())->getShopStaffMemberDetail($billDetail['purchaserId'], 'username');
            if (!empty($purchaserDetial)) {
                $str = $purchaserDetial['username'];
            }
        }
        if (!empty($billDetail['supplierId'])) {
            $supplierDetail = (new SupplierModule())->getSupplierDetailById($billDetail['supplierId'], 'supplierName');
            if (!empty($supplierDetail)) {
                $str = $supplierDetail['supplierName'];
            }
        }
        return (string)$str;
    }

    /**
     * 采购单-商品数量
     * @param int $purchaseId 采购单id
     * @return array
     * */
    public function getPurchaseGoodsNumDetial(int $purchaseId)
    {
        $billGoods = $this->getPurchaseGoodsList($purchaseId, 'relation.id,relation.purchaseStatus,purchaseTotalNum,purchaseOkNum');
        $purchaseGoodsNumTotal = count($billGoods);
        $purchasedGoodsNumTotal = 0;
        $purchaseGoodsNumSum = 0;
        $purchasedGoodsNumSum = 0;
        foreach ($billGoods as $item) {
            if ($item['purchaseStatus'] > 0) {
                $purchasedGoodsNumTotal += 1;
            }
            $purchaseGoodsNumSum += $item['purchaseTotalNum'];
            $purchasedGoodsNumSum += $item['purchaseOkNum'];
        }
        $returnData = array(
            'purchaseGoodsNumTotal' => $purchaseGoodsNumTotal,//采购商品种数
            'purchasedGoodsNumTotal' => $purchasedGoodsNumTotal,//已采购商品种数
            'purchaseGoodsNumSum' => $purchaseGoodsNumSum,//采购商品总数量
            'purchasedGoodsNumSum' => $purchasedGoodsNumSum,//已采购商品总数量
        );
        return $returnData;
    }

    /**
     * 采购单-删除
     * @param int|array $purchaseId
     * @param object $trans
     * @return bool
     * */
    public function delPurchaseBill(array $purchaseId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if (!is_array($purchaseId)) {
            $purchaseId = array($purchaseId);
        }
        $purchaseIdStr = implode(',', $purchaseId);
        $model = new PurchaseBillModel();
        $where = array(
            'purchaseId' => array('in', $purchaseIdStr)
        );
        $saveParams = array(
            'isDelete' => 1,
            'deleteTime' => time(),
        );
        $result = $model->where($where)->save($saveParams);
        if (!$result) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购单-关闭
     * @param int|array $purchaseId
     * @param object $trans
     * @return bool
     * */
    public function closePurchaseBill(array $purchaseId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if (!is_array($purchaseId)) {
            $purchaseId = array($purchaseId);
        }
        $purchaseIdStr = implode(',', $purchaseId);
        $model = new PurchaseBillModel();
        $where = array(
            'purchaseId' => array('in', $purchaseIdStr)
        );
        $saveParams = array(
            'purchaseStatus' => -1,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        $result = $model->where($where)->save($saveParams);
        $purchaseGoodsModel = new PurchaseBillGoodsModel();
        $closeGoodsRes = $purchaseGoodsModel->where($where)->save($saveParams);
        if (!$result || !$closeGoodsRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购单-日志列表
     * @param int $purchaseId 采购单id
     * @return array
     * */
    public function getPurchaseLogById(int $purchaseId)
    {
        $where = array(
            'tableName' => 'wst_purchase_bill',
            'dataId' => $purchaseId,
        );
        $result = (new TableActionLogModule())->getLogListByParams($where);
        return $result;
    }

    /**
     * 采购单-收货
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int purchaseId 采购单id
     * -array goods_data 商品信息
     * --int id 采购商品唯一标识id
     * --float currDeliveryNum 当前收货数量
     * --float deliveryUnitPrice 当前进货价
     * --string deliveryGoodsRemark 商品备注
     * @param object $trans
     * @return array
     * */
    public function deliveryPurchaseBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $purchaseId = $paramsInput['purchaseId'];
        $billDetail = $this->getPurchaseDetailById($purchaseId);
        if (empty($billDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据有误');
        }
        if ($billDetail['purchaseStatus'] == -1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据已关闭，不能操作收货');
        }
        if ($billDetail['purchaseStatus'] == 3) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据已收货完成，请勿重复收货');
        }
        if (empty($paramsInput['loginInfo'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '缺少登陆者信息');
        }
        if (empty($paramsInput['goods_data'])) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品');
        }
        $loginInfo = $paramsInput['loginInfo'];
        $goodsData = $paramsInput['goods_data'];
        $datetime = date('Y-m-d H:i:s');
        $updateGoodsData = array();//需要修改的商品数据
        $currDeliveryNum = 0;//本次收货的数量总和
        $currGoodsDeliveryNum = '';//本次收货商品记录
        $warehouseGoodsData = array();//入库商品
        foreach ($goodsData as $detail) {
            $id = (int)$detail['id'];
            $purGoodsDetial = $this->getPurchaseGoodsDetailById($id);
            if (empty($purGoodsDetial)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息传参有误');
            }
            $goodsName = $purGoodsDetial['goodsName'];
            if ((float)$detail['deliveryUnitPrice'] < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入正确的进货价');
            }
            if ($purGoodsDetial['purchaseStatus'] == 2) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}已收货完成，请勿重复收货");
            }
            if ((float)$detail['currDeliveryNum'] <= 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}收货数量必须大于0");
            }
            $currGoodsDeliveryNum .= " {$goodsName}{$purGoodsDetial['skuSpecStr']}本次收货数量：{$detail['currDeliveryNum']} ";
            $updateGoodsDataDetail = array(
                'id' => $id,
                'purchaseStatus' => 1,
                'purchasePrice' => $detail['deliveryUnitPrice'],
                'purchasePriceTotal' => bc_math($detail['deliveryUnitPrice'], $purGoodsDetial['purchaseTotalNum'], 'bcmul', 2),
                'purchaseOkNum' => bc_math($detail['currDeliveryNum'], $purGoodsDetial['purchaseOkNum'], 'bcadd', 3),
                'updateTime' => $datetime,
            );
            if (!empty($detail['deliveryGoodsRemark'])) {
                $updateGoodsDataDetail['goodsRemark'] = (string)$detail['goodsRemark'];
            }
            if ((float)$updateGoodsDataDetail['purchaseOkNum'] >= (float)$purGoodsDetial['purchaseTotalNum']) {
                $updateGoodsDataDetail['purchaseStatus'] = 2;
            }
            $updateGoodsData[] = $updateGoodsDataDetail;
            $currDeliveryNum += (float)$detail['currDeliveryNum'];
            $goodsId = $purGoodsDetial['goodsId'];
            $skuId = $purGoodsDetial['skuId'];
            $warehouseGoodsData[] = array(
                'goodsId' => $goodsId,
                'skuId' => $skuId,
                'warehousNumTotal' => $purGoodsDetial['purchaseTotalNum'],
                'warehousPrice' => $purGoodsDetial['purchasePrice'],
                'goodsRemark' => '',
            );
//            if (empty($skuId)) {
//                $updateGoodsInfo = array(
//                    'goodsId' => $goodsId,
//                    'goodsUnit' => $updateGoodsDataDetail['purchasePrice'],
//                );
//                $goodsModule->editGoodsInfo($updateGoodsInfo);
//            } else {
//                $updateSkuInfo = array(
//                    'skuId' => $skuId,
//                    'purchase_price' => $updateGoodsDataDetail['purchasePrice'],
//                );
//                $goodsModule->editSkuInfo($updateSkuInfo);
//            }
        }
        $purGoodsModel = new PurchaseBillGoodsModel();
        $updateRes = $purGoodsModel->saveAll($updateGoodsData, 'wst_purchase_bill_goods', 'id');
        if (!$updateRes) {
            $dbTrans->rollback();
            returnData(false, ExceptionCodeEnum::FAIL, 'error', '收货失败', '采购商品信息更新失败');
        }
        $purGoodsNumDetial = $this->getPurchaseGoodsNumDetial($purchaseId);
        $nowPurGoodsList = $this->getPurchaseGoodsList($purchaseId);
        $billAmount = 0;//单据金额
        $deliveryAmount = 0;//收货金额
        foreach ($nowPurGoodsList as $nowDetail) {
            $billAmount += (float)$nowDetail['purchasePriceTotal'];
            $deliveryAmount += (float)(bc_math($nowDetail['purchasePrice'], $nowDetail['purchaseOkNum'], 'bcmul', 2));
        }
        $logRemark = "部分收货，（{$currGoodsDeliveryNum}）";
        $billData = array(
            'purchaseId' => $purchaseId,
            'purchaseStatus' => 1,
            'billAmount' => $billAmount,
            'deliveryAmount' => $deliveryAmount,
            'updateTime' => $datetime,
        );
        if ($purGoodsNumDetial['purchasedGoodsNumSum'] >= $purGoodsNumDetial['purchaseGoodsNumSum']) {
            $logRemark = "已全部收货，（{$currGoodsDeliveryNum}）";
            $billData['purchaseStatus'] = 2;
        }
        $saveBillRes = $this->savePurchaseBill($billData, $dbTrans);
        if (!$saveBillRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "收货失败", "单据信息更新失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_bill',
            'dataId' => $purchaseId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'purchaseStatus',
            'fieldValue' => $billData['purchaseStatus'],
            'remark' => $logRemark,
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "收货失败", "单据日志记录失败");
        }
        if ($billData['purchaseStatus'] > 0) {//创建入库单
            $warehousingModule = new WarehousingBillModule();
            if (!$warehousingModule->isExistRelationBill($purchaseId, 1)) {
                $warehousBill = array(
                    'loginInfo' => $loginInfo,
                    'billType' => 1,
                    'relationBillId' => $billDetail['purchaseId'],
                    'relationBillNo' => $billDetail['billNo'],
                    'goodsData' => $warehouseGoodsData
                );
                $wareRes = $warehousingModule->createWarehousingBill($warehousBill, $dbTrans);
                if ($wareRes['code'] != ExceptionCodeEnum::SUCCESS) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "收货失败", "入库单创建失败");
                }
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 订单商品采购-商品列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -string orderNo 订单号
     * -datetime ordertime_start 下单时间-开始时间
     * -datetime ordertime_end 下单时间-结束时间
     * -date require_date 期望送达日期
     * -int cat_id 店铺分类id
     * -int goodsId 商品id
     * -int skuId 规格id
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 是否导出(0:否 1:是)
     * -int isNeedMerge 是否合并数据(0:否 1:是)
     * @return array
     * */
    public function getOrderGoodsPurchaseList(array $paramsInput)
    {
        $shopId = (int)$paramsInput['shopId'];
        $purGoodsModel = new PurchaseGoodsModel();
        $field = 'pur_g.orderId,pur_g.goodsId,pur_g.goodsNums,pur_g.goodsPrice,pur_g.skuId,pur_g.skuSpecAttr,pur_g.goodsName,pur_g.goodsThums,pur_g.remarks';
        $field .= ',goods.shopCatId1,goods.shopCatId2,goods.goodsImg,goods.goodsSpec,goods.unit';
        $where = "orders.shopId={$shopId} and orders.orderFlag=1 and users.userFlag=1 ";
        if (!empty($paramsInput['ordertime_start'])) {
            $where .= " and orders.createTime >= '{$paramsInput['ordertime_start']}' ";
        }
        if (!empty($paramsInput['ordertime_end'])) {
            $where .= " and orders.createTime <= '{$paramsInput['ordertime_end']}' ";
        }
        if (!empty($paramsInput['require_date'])) {
            $require_date_start = $paramsInput['require_date'] . ' 00:00:00';
            $require_date_end = $paramsInput['require_date'] . ' 23:59:59';
            $where .= " and orders.requireTime between '{$require_date_start}' and '{$require_date_end}' ";
        }
        if (!empty($paramsInput['statusMark'])) {
            switch ($paramsInput['statusMark']) {
                case 'toBePaid'://待付款
                    $where .= " AND orders.orderStatus = -2 ";
                    break;
                case 'toBeAccepted'://待接单
                    $where .= " AND orders.orderStatus = 0 ";
                    break;
                case 'toBeDelivered'://待发货
                    $where .= " AND orders.orderStatus IN(1,2) ";
                    break;
                case 'toBeReceived'://待收货
                    $where .= " AND orders.orderStatus IN(3) and isSelf=0 ";
                    break;
                case 'confirmReceipt'://已完成
                    $where .= " AND orders.orderStatus = 4 ";
                    break;
                case 'toBePickedUp'://待取货,自提订单,商家发货后
                    $where .= " AND orders.orderStatus = 3 and o.isSelf=1 ";
                    break;
                case 'takeOutDelivery'://外卖配送
                    $where .= " AND orders.orderStatus IN(-6,7,8,9,10,11,16,17) ";
                    break;
                case 'invalid'://无效订单(用户取消或商家拒收)
                    $where .= " AND orders.orderStatus IN(-1,-3,-4,-5,-6,-7,-8) ";
                    break;
            }
        }
        if (!empty($paramsInput['orderNo'])) {
            $where .= " and orders.orderNo like '%{$paramsInput['orderNo']}%'";
        }
        if (!empty($paramsInput['goodsId'])) {
            $where .= " and pur_g.goodsId={$paramsInput['goodsId']} ";
        }
        if (!empty($paramsInput['skuId'])) {
            $where .= " and pur_g.skuId={$paramsInput['skuId']} ";
        }
        $catModule = new GoodsCatModule();
        if (!empty($paramsInput['cat_id'])) {
            $catId = (int)$paramsInput['cat_id'];
            $catDetail = $catModule->getShopCatDetailById($catId);
            if (!empty($catDetail)) {
                if ($catDetail['level'] == 1) {
                    $where .= " and goods.shopCatId1={$catId} ";
                } elseif ($catDetail['level'] == 2) {
                    $where .= " and goods.shopCatId2={$catId} ";
                }
            }
        }
        if ($paramsInput['export'] == 1 || $paramsInput['onekeyPurchase'] == 1) {
            if ($paramsInput['isNeedMerge'] == 1) {
                $result = $purGoodsModel
                    ->alias('pur_g')
                    ->join("left join wst_goods goods on goods.goodsId=pur_g.goodsId")
                    ->join("left join wst_orders orders on orders.orderId=pur_g.orderId")
                    ->join("left join wst_users users on users.userId=orders.userId")
                    ->where($where)
                    ->field($field)
                    ->group('pur_g.goodsId,pur_g.skuId')
                    ->select();
            } else {
                $result = $purGoodsModel
                    ->alias('pur_g')
                    ->join("left join wst_goods goods on goods.goodsId=pur_g.goodsId")
                    ->join("left join wst_orders orders on orders.orderId=pur_g.orderId")
                    ->join("left join wst_users users on users.userId=orders.userId")
                    ->where($where)
                    ->field($field)
                    ->select();
                return $result;
            }
        } else {
            $result = $purGoodsModel
                ->alias('pur_g')
                ->join("left join wst_goods goods on goods.goodsId=pur_g.goodsId")
                ->join("left join wst_orders orders on orders.orderId=pur_g.orderId")
                ->join("left join wst_users users on users.userId=orders.userId")
                ->where($where)
                ->field($field)
                ->limit(($paramsInput['page'] - 1) * $paramsInput['pageSize'], $paramsInput['pageSize'])
                ->group('pur_g.goodsId,pur_g.skuId')
                ->select();
        }
        if ($paramsInput['isNeedMerge'] == 1) {
            $result1 = $purGoodsModel
                ->alias('pur_g')
                ->join("left join wst_goods goods on goods.goodsId=pur_g.goodsId")
                ->join("left join wst_orders orders on orders.orderId=pur_g.orderId")
                ->join("left join wst_users users on users.userId=orders.userId")
                ->where($where)
                ->field($field)
                ->select();
            foreach ($result as $key => &$item) {
                $item['goodsNums'] = 0;
                $item['key_id'] = $key + 1;
                $item['shopCat1Name'] = '';
                $item['shopCat2Name'] = '';
                $shopCat1Detail = $catModule->getShopCatDetailById($item['shopCatId1']);
                if (!empty($shopCat1Detail)) {
                    $item['shopCat1Name'] = $shopCat1Detail['catName'];
                }
                $shopCat2Detail = $catModule->getShopCatDetailById($item['shopCatId2']);
                if (!empty($shopCat2Detail)) {
                    $item['shopCat2Name'] = $shopCat2Detail['catName'];
                }
                foreach ($result1 as $item1) {
                    if ($item1['goodsId'] == $item['goodsId'] && $item1['skuId'] == $item['skuId']) {
                        $item['goodsNums'] += $item1['goodsNums'];
                    }
                }
                $item['goodsNums'] = (string)$item['goodsNums'];//统一格式
            }
            unset($item);
        }
        $total_result = $purGoodsModel
            ->alias('pur_g')
            ->join("left join wst_goods goods on goods.goodsId=pur_g.goodsId")
            ->join("left join wst_orders orders on orders.orderId=pur_g.orderId")
            ->join("left join wst_users users on users.userId=orders.userId")
            ->where($where)
            ->field($field)
            ->group('pur_g.goodsId,pur_g.skuId')
            ->select();
        $returnGoods = array();
        $goodsMoudle = new GoodsModule();
        foreach ($result as $detail) {
            $returnDetail = array(
                'key_id' => $detail['key_id'],
                'goodsId' => $detail['goodsId'],
                'skuId' => $detail['skuId'],
                'goodsName' => $detail['goodsName'],
                'goodsImg' => $detail['goodsImg'],
                'goodsSpec' => $detail['goodsSpec'],
                'unit' => $detail['unit'],
                'purchasePrice' => $detail['goodsPrice'],
                'purchaseTotalNum' => $detail['goodsNums'],
                'smartRemark' => $detail['remarks'],
                'shopCat1Name' => $detail['shopCat1Name'],
                'shopCat2Name' => $detail['shopCat2Name'],
                'skuSpecStrTwo' => '',
            );
            if (!empty($detail['skuId'])) {
                $skuDetail = $goodsMoudle->getSkuSystemInfoById($detail['skuId'], 2);
                if (!empty($skuDetail)) {
                    $returnDetail['goodsImg'] = $skuDetail['skuGoodsImg'];
                    $returnDetail['unit'] = $skuDetail['unit'];
                    $returnDetail['skuSpecStrTwo'] = $skuDetail['skuSpecAttrTwo'];
                }
            }
            $returnGoods[] = $returnDetail;
        }
        if ($paramsInput['onekeyPurchase'] == 1 || $paramsInput['export'] == 1) {
            return $returnGoods;
        }
        $count = count((array)$total_result);
        $returnData = array(
            'root' => (array)$returnGoods,
            'currPage' => $paramsInput['page'],
            'pageSize' => $paramsInput['pageSize'],
            'start' => ($paramsInput['page'] - 1) * $paramsInput['pageSize'],
            'total' => $count,
            'totalPage' => ceil(($count / $paramsInput['pageSize'])),
        );
        return $returnData;
    }

    /**
     * 订单商品采购-删除商品-多条件
     * @param int $orderId 订单id
     * @param int $goodsId 商品id
     * @param int $skuId 规格id
     * @param object $trans
     * @return bool
     * */
    public function delPurchaseGoodsByParams(int $orderId, int $goodsId, $skuId = 0, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new PurchaseGoodsModel();
        $where = array(
            'orderId' => $orderId,
            'goodsId' => $goodsId,
            'skuId' => $skuId,
        );
        $res = $model->where($where)->delete();
        if (!$res) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购单-打印-记录打印次数
     * @param int $purchaseId 采购单id
     * @param object $trans
     * @return bool
     * */
    public function incPurchaseBillPrintNum(int $purchaseId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new PurchaseBillModel();
        $result = $model->where(array('purchaseId' => $purchaseId))->setInc('printNum');
        if (!$result) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购单-获取采购单关联的入库单id
     * @param int $purchaseId 采购单id
     * @return int
     * */
    public function getPurchaseBillRelationWarehousingId(int $purchaseId)
    {
        $model = new WarehousingBillModel();
        $warehousingId = $model->where(
            array(
                'relationBillId' => $purchaseId,
                'billType' => 1,
                'isDelete' => 0,
            )
        )->getField('warehousingId');
        return (int)$warehousingId;
    }


    /**
     * 采购退货单-保存
     * @param array $paramsInput
     * wst_purchase_return_bill表字段
     * @param object $trans
     * @return int
     * */
    public function savePurchaseReturnBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new PurchaseReturnBillModel();
        $datetime = date("Y-m-d H:i:s");
        $saveParams = array(
            'shopId' => null,
            'purchaseId' => null,
            'creatorId' => null,
            'creatorName' => null,
            'returnBillRemark' => null,
            'returnBillAmount' => null,
            'billStatus' => null,
            'isDelete' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = $datetime;
            }
        }
        if (empty($paramsInput['returnId'])) {
            $saveParams['createTime'] = $datetime;
            $returnId = $model->add($saveParams);
            if (empty($returnId)) {
                $dbTrans->rollback();
                return 0;
            }
            $updateParams = array(
                'returnId' => $returnId,
                'billNo' => 'CGTH' . date('Ymd') . str_pad($returnId, 10, "0", STR_PAD_LEFT),
            );
            $updateRes = $model->where(array('returnId' => $returnId))->save($updateParams);
            if ($updateRes === false) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $returnId = $paramsInput['returnId'];
            $where = array(
                'returnId' => $returnId
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
        return (int)$returnId;
    }

    /**
     * 采购单-更新采购单退货状态
     * @param int $purchaseId 采购单id
     * @param object $trans
     * @return bool
     * */
    public function autoUpdatePurchaseBillStatus(int $purchaseId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $billGoods = $this->getPurchaseGoodsList($purchaseId);
        $returnBillStatus = 0;//单据退货状态(0:未退货 1:部分退货 2:全部退货)
        $warehouseOkNumTotal = array_sum(array_column($billGoods, 'warehouseOkNum'));//商品入库总计
        $returnGoodsNumTotal = array_sum(array_column($billGoods, 'returnOKGoodsNum'));//商品退货数量总计
        if ($returnGoodsNumTotal > 0) {
            $returnBillStatus = 1;
        }
        if ($returnGoodsNumTotal > $warehouseOkNumTotal) {
            $returnBillStatus = 2;
        }
        $model = new PurchaseBillModel();
        $where = array(
            'purchaseId' => $purchaseId
        );
        $saveParams = array(
            'returnBillStatus' => $returnBillStatus,
            'updateTime' => date('Y-m-d H:i:s'),
        );
        $saveRes = $model->where($where)->save($saveParams);
        if (!$saveRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购退货单-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int billType 单据类型(1:市场自采 2:供应商直供) 不传值默认全部
     * -int purchaserId 采购员id
     * -int supplierId 供应商id
     * -date createDateStart 创建日期区间-开始日期
     * -date createDateEnd 创建日期区间-结束日期
     * -string returnBillNo 退货单号
     * -string purchaseBillNo 采购单号
     * -string usePage 是否使用分页(0:不使用 1:使用)
     * -int page 页码
     * -int pageSize 每页条数
     * @return array
     * */
    public function getPurchaseReturnBillList(array $paramsInput)
    {
        $searchParams = array(
            'shopId' => 0,
            'billType' => '',//单据类型(1:市场自采 2:供应商直供) 不传值默认全部
            'purchaserId' => '',//采购员id
            'supplierId' => '',//供应商id
            'createDateStart' => '',//创建日期区间-开始日期
            'createDateEnd' => '',//创建日期区间-结束日期
            'returnBillNo' => '',//退货单号
            'purchaseBillNo' => '',//采购单号
            'usePage' => 1,//是否使用分页(0:不使用 1:使用)
            'page' => 1,//页码
            'pageSize' => 15,//每页条数
        );
        parm_filter($searchParams, $paramsInput);
        $shopId = $searchParams["shopId"];
        $model = new PurchaseReturnBillModel();
        $where = " returnb.shopId={$shopId} and returnb.isDelete=0 ";
        if (!empty($searchParams['billType'])) {
            $where .= " and purchase.billType={$searchParams['billType']} ";
        }
        if (!empty($searchParams['purchaserId'])) {
            $where .= " and purchase.purchaserId={$searchParams['purchaserId']} ";
        }
        if (!empty($searchParams['supplierId'])) {
            $where .= " and purchase.supplierId={$searchParams['supplierId']} ";
        }
        if (!empty($searchParams['createDateStart'])) {
            $where .= " and returnb.createTime>='{$searchParams['createDateStart']} 00:00:00' ";
        }
        if (!empty($searchParams['createDateEnd'])) {
            $where .= " and returnb.createTime<='{$searchParams['createDateEnd']} 23:59:59' ";
        }
        if (!empty($searchParams['returnBillNo'])) {
            $where .= " and returnb.billNo like '%{$searchParams['returnBillNo']}%' ";
        }
        if (!empty($searchParams['purchaseBillNo'])) {
            $where .= " and purchase.billNo like '%{$searchParams['purchaseBillNo']}%' ";
        }
        $field = "returnb.returnId,returnb.billNo,returnb.returnBillAmount,returnb.returnBillRemark,returnb.creatorName,returnb.billStatus,returnb.createTime";
        $field .= ",purchase.purchaseId,purchase.billNo as purchaseBillNo,purchase.purchaserId,purchase.supplierId,purchase.billType";
        $sql = $model
            ->alias('returnb')
            ->join("left join wst_purchase_bill purchase on purchase.purchaseId=returnb.purchaseId")
            ->where($where)
            ->field($field)
            ->order('returnb.createTime desc')
            ->buildSql();
        if ($searchParams['usePage'] == 1) {
            $result = $model->pageQuery($sql, $searchParams['page'], $searchParams['pageSize']);
        } else {
            $result = array(
                'root' => $model->query($sql),
            );
        }
        $purchaseBillEnum = new PurchaseBillEnum();
        foreach ($result['root'] as &$item) {
            $item['billTypeName'] = $purchaseBillEnum->getBillType($item['billType']);
            $item['purchaserOrSupplier'] = $this->getPurchaserOrSupplier($item['purchaseId']);
            $item['billStatusName'] = $purchaseBillEnum->getBillReturnStatus()[$item['billStatus']];
        }
        unset($item);
        if ($searchParams['usePage'] != 1) {
            return $result['root'];
        }
        return $result;
    }

    /**
     * 采购退货单-详情-id查找
     * @param int $returnId 采购退货单id
     * @return array
     * */
    public function getPurchaseReturnBillDetailById(int $returnId)
    {
        $model = new PurchaseReturnBillModel();
        $field = "returnb.returnId,returnb.billNo,returnb.returnBillAmount,returnb.returnBillRemark,returnb.creatorName,returnb.billStatus,returnb.createTime,returnb.shopId";
        $field .= ",purchase.purchaseId,purchase.billNo as purchaseBillNo,purchase.purchaserId,purchase.supplierId,purchase.billType";
        $where = array(
            'returnb.returnId' => $returnId,
            'returnb.isDelete' => 0,
        );
        $result = $model
            ->alias('returnb')
            ->join("left join wst_purchase_bill purchase on purchase.purchaseId=returnb.purchaseId")
            ->where($where)
            ->field($field)
            ->find();
        if (empty($result)) {
            return array();
        }
        $purchaseBillEnum = new PurchaseBillEnum();
        $result['billTypeName'] = $purchaseBillEnum->getBillType($result['billType']);
        $result['purchaserOrSupplier'] = $this->getPurchaserOrSupplier($result['purchaseId']);
        $result['billStatusName'] = $purchaseBillEnum->getBillReturnStatus()[$result['billStatus']];
        return (array)$result;
    }

    /**
     * 采购退货单-商品列表-采购退货单id查找
     * @param int $returnId 采购退货单id
     * @return array
     * */
    public function getPurchaseReturnBillGoodsList(int $returnId)
    {
        $model = new PurchaseReturnBillGoodsModel();
        $where = " goods.returnId={$returnId} and goods.isDelete=0 ";
        $where .= " and bill.isDelete=0 ";
        $feild = "goods.id,goods.goodsId,goods.skuId,goods.goodsName,goods.goodsImg,goods.describe,goods.unitName,goods.skuSpecStr,goods.returnGoodsRemark,goods.warehouseOkNum,goods.warehousePrice,goods.purchasePrice,goods.returnGoodsNum,returnGoodsPrice,goods.returnGoodsPriceTotal";
        $result = $model
            ->alias("goods")
            ->join("left join wst_purchase_return_bill bill on bill.returnId=goods.returnId")
            ->where($where)
            ->field($feild)
            ->select();
        if (empty($result)) {
            return array();
        }
        foreach ($result as $key => &$item) {
            $item["keyNum"] = $key + 1;
        }
        unset($item);
        return $result;
    }

    /**
     * 采购退货单商品-删除
     * @return array|int $idArr 采购退货单商品唯一关联id
     * @return bool
     * */
    public function delPurchaseReturnGoodsById($idArr, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        if (is_numeric($idArr)) {
            $idArr = array($idArr);
        }
        $model = new PurchaseReturnBillGoodsModel();
        $where = array(
            'id' => array('in', $idArr)
        );
        $saveParams = array(
            'isDelete' => 1,
            'deleteTime' => date('Y-m-d H:i:s'),
        );
        $saveRes = $model->where($where)->save($saveParams);
        if (!$saveRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

    /**
     * 采购退货单-删除
     * @param int $returnId 采购退货单Id
     * @param object $trans
     * @return bool
     * */
    public function delPurchaseReturnBill(int $returnId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $where = array(
            'returnId' => $returnId
        );
        $billModel = new PurchaseReturnBillModel();
        $dateTime = date('Y-m-d H:i:s');
        $saveBillParams = array(
            "isDelete" => 1,
            "deleteTime" => $dateTime,
        );
        $delBillRes = $billModel->where($where)->save($saveBillParams);
        if (!$delBillRes) {
            $dbTrans->rollback();
            return false;
        }
        $billGoodsModel = new PurchaseReturnBillGoodsModel();
        $delBillGoodsRes = $billGoodsModel->where($where)->save($saveBillParams);
        if (!$delBillGoodsRes) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }
}