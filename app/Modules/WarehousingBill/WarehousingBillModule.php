<?php
/**
 * 入库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-23
 * Time: 19:11
 */

namespace App\Modules\WarehousingBill;


use App\Enum\ExceptionCodeEnum;
use App\Enum\WarehousingBill\WarehousingBillEnum;
use App\Models\WarehousingBillGoodsModel;
use App\Models\WarehousingBillModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use Think\Model;

class WarehousingBillModule
{
    /**
     * 入库单-创建入库单
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int billType 单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
     * -int relationBillId 关联单id
     * -string relationBillNo 关联单号
     * -string billRemark 单据备注
     * -array goodsData 入库单商品信息
     * --int goodsId 商品id
     * --int skuId 规格id
     * --float warehousNumTotal 应入库数量
     * --float warehousPrice 入库单价
     * --string goodsRemark 商品备注
     * @param object $trans
     * @return array
     * */
    public function createWarehousingBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $loginInfo = $paramsInput['loginInfo'];
        $goodsData = (array)$paramsInput['goodsData'];
        if (empty($goodsData)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '入库单商品不能为空');
        }
        $warehousGoodsData = array();//入库单商品
        $goodsModule = new GoodsModule();
        $warehousingBillAmount = 0;//应入库金额
        $datetime = date('Y-m-d H:i:s');
        foreach ($goodsData as $item) {
            $goodsId = (int)$item['goodsId'];
            $skuId = (int)$item['skuId'];
            $warehousNumTotal = (float)$item['warehousNumTotal'];//应入库数量
            $warehousPrice = (float)$item['warehousPrice'];//入库单价
            if ($warehousNumTotal <= 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入正确的入库数量');
            }
            if ($warehousPrice < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请输入正确的入库价');
            }
            $goodsDetail = $goodsModule->getGoodsInfoById($goodsId, 'goodsId,goodsName,unit,goodsImg,goodsSn,goodsSpec', 2);
            if (empty($goodsDetail)) {
                continue;
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
            }
            $goodsName = $goodsDetail['goodsName'];
            $warehousGoodsDetail = array(
                'goodsId' => $goodsId,
                'skuId' => $skuId,
                'goodsName' => $goodsName,
                'goodsCode' => $goodsDetail['goodsSn'],
                'goodsImg' => $goodsDetail['goodsImg'],
                'skuSpecStr' => '',
                'describe' => (string)$goodsDetail['goodsSpec'],
                'unitName' => $goodsDetail['unit'],
                'warehousNumTotal' => $warehousNumTotal,
                'warehousPrice' => $warehousPrice,
                'warehousePriceTotal' => bc_math($warehousNumTotal, $warehousPrice, 'bcmul', 2),
                'goodsRemark' => (string)$item['goodsRemark'],
                'createTime' => $datetime,
                'updateTime' => $datetime,
            );
            if (!empty($skuId)) {
                $skuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
                if (empty($skuDetail)) {
                    continue;
                    //查不到就直接跳过吧
//                    $dbTrans->rollback();
//                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}规格信息有误");
                }
                $warehousGoodsDetail['goodsCode'] = $skuDetail['skuBarcode'];
                $warehousGoodsDetail['skuSpecStr'] = $skuDetail['skuSpecAttrTwo'];
                $warehousGoodsDetail['unitName'] = $skuDetail['unit'];
                $warehousGoodsDetail['goodsImg'] = $skuDetail['skuGoodsImg'];
            }
            $uniqueTag = $goodsId . '@' . $skuId;
            if (isset($warehousGoodsData[$uniqueTag])) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品信息中存在重复数据");
            }
            $warehousGoodsData[$uniqueTag] = $warehousGoodsDetail;
            $warehousingBillAmount += (float)$warehousGoodsDetail['warehousePriceTotal'];
        }
        if (empty($warehousGoodsData)) {
            if (empty($trans)) {
                $dbTrans->commit();
            }
            return returnData(true);
        }
        $warehousGoodsData = array_values($warehousGoodsData);
        $billData = array(
            'shopId' => $loginInfo['shopId'],
            'billType' => 0,
            'relationBillId' => 0,
            'relationBillNo' => '',
            'billRemark' => '',
            'creatorId' => $loginInfo['user_id'],
            'creatorName' => $loginInfo['user_username'],
            'warehousingBillAmount' => $warehousingBillAmount,
        );
        parm_filter($billData, $paramsInput);
        if (in_array($paramsInput['billType'], array(7))) {
            $billData['warehousingStatus'] = 1;
            $billData['billInputStatus'] = 2;
            $billData['warehousingTime'] = $datetime;
            $billData['warehousingBillOkAmount'] = $billData['warehousingBillAmount'];
        }
        $warehousingId = $this->saveWarehousingBill($billData, $dbTrans);
        if (!$warehousingId) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '入库单创建失败');
        }
        foreach ($warehousGoodsData as &$item) {
            if (in_array($paramsInput['billType'], array(7))) {
                $item['warehouseStatus'] = 1;
                $item['goodsInputStatus'] = 1;
                $item['warehouseOkNum'] = $item['warehousNumTotal'];
            }
            $item['warehousingId'] = $warehousingId;
        }
        unset($item);
        $warehousingGoodsModel = new WarehousingBillGoodsModel();
        $addGoodsRes = $warehousingGoodsModel->addAll($warehousGoodsData);
        if (!$addGoodsRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '入库单创建失败', '入库商品关联失败');
        }
        $logRemark = '已创建入库单，等待入库';
        if (in_array($paramsInput['billType'], array(7))) {
            $logRemark = '已创建入库单';
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_warehousing_bill',
            'dataId' => $warehousingId,
            'actionUserId' => $billData['creatorId'],
            'actionUserName' => $billData['creatorName'],
            'fieldName' => 'warehousingStatus',
            'fieldValue' => 0,
            'remark' => $logRemark,
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "入库单创建失败", "单据日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 入库单-保存
     * -wst_warehousing_bill表字段
     * @param object $trans
     * @return int
     * */
    public function saveWarehousingBill(array $paramsInput, $trans = null)
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
            'warehousingStatus' => null,
            'billInputStatus' => null,
            'billType' => null,
            'relationBillId' => null,
            'billRemark' => null,
            'relationBillNo' => null,
            'warehousingTime' => null,
            'creatorId' => null,
            'creatorName' => null,
            'warehousingBillAmount' => null,
            'warehousingBillOkAmount' => null,
            'isDelete' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = time();
            }
        }
        $model = new WarehousingBillModel();
        if (empty($paramsInput['warehousingId'])) {
            $saveParams['createTime'] = $datetime;
            $warehousingId = $model->add($saveParams);
            if (empty($warehousingId)) {
                $dbTrans->rollback();
                return 0;
            }
            $billNo = 'RK' . date('Ymd') . str_pad($warehousingId, 10, "0", STR_PAD_LEFT);
            $saveParams = array(
                'warehousingId' => $warehousingId,
                'billNo' => $billNo,
            );
            $this->saveWarehousingBill($saveParams, $trans);
        } else {
            $warehousingId = $paramsInput['warehousingId'];
            $where = array(
                'warehousingId' => $warehousingId
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
        return (int)$warehousingId;
    }

    /**
     * 入库单-是否已存在关联入库单
     * @param int $relationBillId 关联单id
     * @param int $billType 入库单类型
     * @return bool
     * */
    public function isExistRelationBill(int $relationBillId, int $billType)
    {
        $model = new WarehousingBillModel();
        $where = array(
            'isDelete' => 0,
            'relationBillId' => $relationBillId,
            'billType' => $billType,
        );
        $count = $model->where($where)->count();
        if ($count > 0) {
            return true;
        }
        return false;
    }

    /**
     * 入库单-单据列表
     * @param array $paramInput
     * -int shopId 门店id
     * -int warehousingStatus 入库状态(0:未入库 1:已入库)
     * -int billType 单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
     * -int billInputStatus 单据录入状态(0:未录入 1:部分录入 2:已录入)
     * -int dateType 日期类型(1:制单日期 2:入库日期)
     * -date dateStart 日期区间-开始日期
     * -date dateEnd 日期区间-结束日期
     * -string billNo 单号
     * -string creatorName 制单人
     * -string relationBillNo 关联单号
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getWarehousingBillList(array $paramInput)
    {
        $shopId = $paramInput['shopId'];
        $where = "shopId={$shopId} and isDelete=0 ";
        if (isset($paramInput['warehousingStatus'])) {
            if (is_numeric($paramInput['warehousingStatus'])) {
                $where .= " and warehousingStatus={$paramInput['warehousingStatus']} ";
            }
        }
        if (isset($paramInput['billType'])) {
            if (is_numeric($paramInput['billType'])) {
                $where .= " and billType={$paramInput['billType']} ";
            }
        }
        if (isset($paramInput['billInputStatus'])) {
            if (is_numeric($paramInput['billInputStatus'])) {
                $where .= " and billInputStatus={$paramInput['billInputStatus']} ";
            }
        }
        if (isset($paramInput['dateType'])) {
            if ($paramInput['dateType'] == 1) {
                $dateVariableName = 'createTime';
            } else {
                $dateVariableName = 'warehousingTime';
            }
            if (!empty($paramInput['dateStart'])) {
                $where .= " and {$dateVariableName} >= '{$paramInput['dateStart']} 00:00:00' ";
            }
            if (!empty($paramInput['dateEnd'])) {
                $where .= " and {$dateVariableName} <= '{$paramInput['dateEnd']} 23:59:59' ";
            }
        }
        if (!empty($paramInput['creatorName'])) {
            $where .= " and creatorName like '%{$paramInput['creatorName']}%' ";
        }
        if (!empty($paramInput['billNo'])) {
            $where .= " and billNo like '%{$paramInput['billNo']}%' ";
        }
        if (!empty($paramInput['relationBillNo'])) {
            $where .= " and relationBillNo like '%{$paramInput['relationBillNo']}%' ";
        }
        $billField = 'warehousingId,shopId,billNo,warehousingStatus,billInputStatus,billType,relationBillId,relationBillNo,warehousingTime,creatorId,creatorName,warehousingBillAmount,warehousingBillOkAmount,billRemark,createTime,printNum';
        $model = new WarehousingBillModel();
        $sql = $model
            ->alias('bill')
            ->where($where)
            ->field($billField)
            ->order('createTime desc')
            ->buildSql();
        $result = $model->pageQuery($sql, $paramInput['page'], $paramInput['pageSize']);
        $rawData = $model->query($sql);
        $billAmountSum = 0;//应入库金额总计
        $billOkAmountSum = 0;//已入库金额总计
        foreach ($rawData as $rawInfo) {
            $billAmountSum += $rawInfo['warehousingBillAmount'];
            $billOkAmountSum += $rawInfo['warehousingBillOkAmount'];
        }
        if (!empty($result['root'])) {
            $list = $result['root'];
            $warehousingEnum = new WarehousingBillEnum();
            foreach ($list as &$item) {
                $item['warehousingTime'] = (string)$item['warehousingTime'];
                $item['warehousingStatusName'] = $warehousingEnum::getWarehousingStatus()[$item['warehousingStatus']];//入库状态名称
                $item['billTypeName'] = $warehousingEnum::getBillType()[$item['billType']];//单据类型名称
                $item['billInputStatusName'] = $warehousingEnum::getBillInputStatus()[$item['billInputStatus']];
            }
            unset($item);
            $result['root'] = $list;
        }
        $result['billAmountSum'] = $billAmountSum;
        $result['billOkAmountSum'] = $billOkAmountSum;
        return $result;
    }

    /**
     * 入库单-商品列表
     * @param array $paramsInput
     * int shopId 门店id
     * int catid 当前门店分类id
     * int billType 单据类型
     * date warehousingTimeStart 入库日期区间-开始日期
     * date warehousingTimeEnd 入库日期区间-结束日期
     * string goodsKeywords 商品关键字
     * string billNo 入库单号
     * string export 是否导出(0:否 1:是)
     * string page 页码
     * string pageSize 分页条数
     * @return array
     * */
    public function getWarehousingBillGoodsList(array $paramsInput)
    {
        $shopId = (int)$paramsInput['shopId'];
        $where = "bill.shopId={$shopId} and bill.isDelete=0 ";
        $goodsCatMoudle = new GoodsCatModule();
        if (!empty($paramsInput['catid'])) {
            $shopCatDetail = $goodsCatMoudle->getShopCatDetailById($paramsInput['catid']);
            if (!empty($shopCatDetail)) {
                if ($shopCatDetail['level'] = 1) {
                    $where .= " and goods.shopCatId1={$paramsInput['catid']} ";
                } else {
                    $where .= " and goods.shopCatId2={$paramsInput['catid']} ";
                }
            }
        }
        if (!empty($paramsInput['billType'])) {
            $where .= " and bill.billType={$paramsInput['billType']}";
        }
        if (!empty($paramsInput['warehousingTimeStart'])) {
            $where .= " and bill_goods.warehousingTime >= '{$paramsInput['warehousingTimeStart']} 00:00:00 '";
        }
        if (!empty($paramsInput['warehousingTimeEnd'])) {
            $where .= " and bill_goods.warehousingTime <= '{$paramsInput['warehousingTimeEnd']} 23:59:59 '";
        }
        if (!empty($paramsInput['billNo'])) {
            $where .= " and bill.billNo like '%{$paramsInput['billNo']}%' ";
        }
        if (!empty($paramsInput['goodsKeywords'])) {
            $goodsKeywords = $paramsInput['goodsKeywords'];
            if (!preg_match("/([\x81-\xfe][\x40-\xfe])/", $goodsKeywords, $match)) {
                $goodsKeywords = strtoupper($goodsKeywords);
            }
            $where .= " and (bill_goods.goodsName like '%{$paramsInput['goodsKeywords']}%' or goods.py_code like '%{$goodsKeywords}%' or goods.py_initials like '%{$goodsKeywords}%' or bill_goods.goodsCode like '%{$paramsInput['goodsKeywords']}%') ";
        }
        $model = new WarehousingBillGoodsModel();
        $field = 'bill_goods.id,bill_goods.goodsId,bill_goods.skuId,bill_goods.goodsName,bill_goods.goodsImg,bill_goods.goodsCode,bill_goods.skuSpecStr,bill_goods.describe,bill_goods.unitName,bill_goods.warehousNumTotal,bill_goods.warehouseOkNum,bill_goods.warehouseStatus,bill_goods.warehousPrice,bill_goods.warehousePriceTotal,bill_goods.goodsRemark,bill_goods.warehousingTime';
        $field .= ',bill.warehousingId,bill.billNo,bill.billType';
        $field .= ',goods.shopCatId1,goods.shopCatId2';
        $sql = $model
            ->alias('bill_goods')
            ->join('left join wst_warehousing_bill bill on bill.warehousingId=bill_goods.warehousingId')
            ->join('left join wst_goods goods on goods.goodsId=bill_goods.goodsId')
            ->where($where)
            ->field($field)
            ->order('bill.createTime desc')
            ->buildSql();
        if ($paramsInput['export'] == 1) {
            $list = $model->query($sql);
        } else {
            $rawData = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
            $list = $rawData['root'];
        }
        $billAmountSum = 0;//应入库金额
        $billOkAmountSum = 0;//已入库金额
        $warehousingEnum = new WarehousingBillEnum();
        foreach ($list as &$item) {
            $item['warehousingTime'] = (string)$item['warehousingTime'];
            $shopCatId1Detail = $goodsCatMoudle->getShopCatDetailById($item['shopCatId1'], 'catName');
            $shopCatId2Detail = $goodsCatMoudle->getShopCatDetailById($item['shopCatId2'], 'catName');
            $item['shopCatId1Name'] = (string)$shopCatId1Detail['catName'];
            $item['shopCatId2Name'] = (string)$shopCatId2Detail['catName'];
            $item['warehouseNoNum'] = bc_math($item['warehousNumTotal'], $item['warehouseOkNum'], 'bcsub', 3);
            $item['warehouseOkPriceTotal'] = bc_math($item['warehouseOkNum'], $item['warehousPrice'], 'bcmul', 2);//已入库金额小计
            if ((float)$item['warehouseNoNum'] < 0) {
                $item['warehouseNoNum'] = '0';
            }
            $item['billTypeName'] = $warehousingEnum::getBillType()[$item['billType']];
            $billAmountSum += (float)$item['warehousePriceTotal'];
            $billOkAmountSum += (float)$item['warehouseOkPriceTotal'];
        }
        unset($item);
        if ($paramsInput['export'] == 1) {
            return $list;
        }
        $rawData['billAmountSum'] = $billAmountSum;
        $rawData['billOkAmountSum'] = $billOkAmountSum;
        $rawData['root'] = $list;
        return $rawData;
    }


    /**
     * 入库单-单据列表
     * @param int $warehousingId 入库单id
     * @return array
     * */
    public function getWarehousingBillDetailById(int $warehousingId)
    {
        $where = array(
            'warehousingId' => $warehousingId,
            'isDelete' => 0,
        );
        $billField = 'warehousingId,shopId,billNo,warehousingStatus,billInputStatus,billType,relationBillId,relationBillNo,warehousingTime,creatorId,creatorName,warehousingBillAmount,warehousingBillOkAmount,billRemark,createTime,printNum';
        $model = new WarehousingBillModel();
        $result = $model
            ->where($where)
            ->field($billField)
            ->find();
        if (empty($result)) {
            return $result;
        }
        $warehousingEnum = new WarehousingBillEnum();
        $result['warehousingTime'] = (string)$result['warehousingTime'];
        $result['warehousingStatusName'] = $warehousingEnum::getWarehousingStatus()[$result['warehousingStatus']];//入库状态名称
        $result['billInputStatusName'] = $warehousingEnum::getBillInputStatus()[$result['billInputStatus']];//单据录入状态名称
        $result['billTypeName'] = $warehousingEnum::getBillType()[$result['billType']];//单据类型名称
        return $result;
    }


    /**
     * 入库单-单据商品列表-入库单id查找
     * @param int $warehousingId 入库单id
     * @param string [$keywords] 商品关键字
     * @return array
     * */
    public function getWarehousingBillGoodsById(int $warehousingId, $keywords = '')
    {
        $where = "warehousingId={$warehousingId}";
        if (!empty($keywords)) {
            $where .= " and (goodsName like '%{$keywords}%' or goodsCode like '%{$keywords}%')";
        }
        $model = new WarehousingBillGoodsModel();
        $result = $model->where($where)->select();
        $warehousingEnum = new WarehousingBillEnum();
        foreach ($result as $key => &$item) {
            $item['keyNum'] = $key + 1;
            $item['warehousingTime'] = (string)$item['warehousingTime'];
            $item['warehouseOkPriceTotal'] = bc_math($item['warehousPrice'], $item['warehouseOkNum'], 'bcmul', 2);
            $item['warehouseNoNum'] = bc_math($item['warehousNumTotal'], $item['warehouseOkNum'], 'bcsub', 3);
            if ((float)$item['warehouseNoNum'] < 0) {
                $item['warehouseNoNum'] = '0';
            }
            $item['goodsInputStatusName'] = $warehousingEnum::getGoodsInputStatus()[$item['goodsInputStatus']];
            $item['warehouseStatusName'] = $warehousingEnum::getWarehousingStatus()[$item['warehouseStatus']];
        }
        unset($item);
        return (array)$result;
    }

    /**
     * 入库单-日志列表
     * @param int $warehousingId 入库单id
     * @return array
     * */
    public function getWarehousingBillLogById(int $warehousingId)
    {
        $where = array(
            'tableName' => 'wst_warehousing_bill',
            'dataId' => $warehousingId,
        );
        $result = (new TableActionLogModule())->getLogListByParams($where);
        return $result;
    }

    /**
     * 入库单-录入/编辑
     * @param array $paramsInput
     * -array loginInfo 当前登陆者信息
     * -int warehousingId 入库单id
     * -string billRemark 单据备注
     * -array goods_data 商品信息
     * -int id 入库商品关联唯一标识id
     * -float warehouseOkNum 实际入库数量
     * -float warehousPrice 入库单价
     * -string goodsRemark 商品备注
     * @param object $trans
     * @return array
     * */
    public function inputWarehousingGoods(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $loginInfo = $paramsInput['loginInfo'];
        $warehousingId = (int)$paramsInput['warehousingId'];
        $billRemark = (string)$paramsInput['billRemark'];
        $goodsData = $paramsInput['goods_data'];
        $billDetial = $this->getWarehousingBillDetailById($warehousingId);
        if (empty($billDetial)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息异常');
        }
        if ($billDetial['warehousingStatus'] != 0) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已审核/入库的单据不能操作修改');
        }
        if (empty($goodsData)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要入库的商品信息');
        }
        $warehousingBillAmount = 0;//应入库金额
        $warehousingBillOkAmount = 0;//实际入库金额
        $detailRemark = '';
        foreach ($goodsData as $goodsItem) {
            $id = (int)$goodsItem['id'];
            $warehouseOkNum = (float)$goodsItem['warehouseOkNum'];
            $warehousPrice = (float)$goodsItem['warehousPrice'];
            $warehousGoodsDetail = $this->getWarehousingBillGoodsDetailById($id);
            if (empty($warehousGoodsDetail)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前提交的入库商品信息异常');
            }
            $goodsName = $warehousGoodsDetail['goodsName'];
            if ($warehouseOkNum < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}的实际入库数量不合法");
            }
            if ($warehousPrice < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}的入库价格不合法");
            }
            $saveBillGoods = array(
                'id' => $id,
                'warehouseOkNum' => $warehouseOkNum,
                'warehousPrice' => $warehousPrice,
                'goodsRemark' => $goodsItem['goodsRemark'],
                'goodsInputStatus' => 1,
                'warehousePriceTotal' => bc_math($warehousPrice, $warehousGoodsDetail['warehousNumTotal'], 'bcmul', 2),
            );
            $saveBillGoodsRes = $this->saveWarehousingBillGoods($saveBillGoods, $dbTrans);
            if (!$saveBillGoodsRes) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "单据商品信息更新失败");
            }
            if ($saveBillGoods['warehouseOkNum'] != $warehousGoodsDetail['warehouseOkNum']) {
                $detailRemark .= "商品{$goodsName}的实际入库数量从{$warehousGoodsDetail['warehouseOkNum']}变更为{$warehouseOkNum} ";
            }
            if ($saveBillGoods['warehousPrice'] != $warehousGoodsDetail['warehousPrice']) {
                $detailRemark .= "商品{$goodsName}的入库价格从{$warehousGoodsDetail['warehousPrice']}变更为{$warehousPrice} ";
            }
            $warehousingBillAmount += $saveBillGoods['warehousePriceTotal'];
            $warehousingBillOkAmount += bc_math($warehousPrice, $warehouseOkNum, 'bcmul', 2);
        }
        $saveBill = array(
            'warehousingId' => $warehousingId,
            'warehousingBillAmount' => $warehousingBillAmount,
            'warehousingBillOkAmount' => $warehousingBillOkAmount,
            'billRemark' => $billRemark,
            'billInputStatus' => $this->getBillInputStatus($warehousingId),
        );
        $saveBillRes = $this->saveWarehousingBill($saveBill, $dbTrans);
        if (!$saveBillRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "单据信息更新失败");
        }
        if (!empty($detailRemark)) {
            $detailRemark = "（{$detailRemark}）";
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_warehousing_bill',
            'dataId' => $warehousingId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'warehousingStatus',
            'fieldValue' => 0,
            'remark' => "修改了单据信息$detailRemark",
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "入库单创建失败", "单据日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 入库单-获取入库单的录入状态
     * @param int $warehousingId 入库单id
     * @return int
     * */
    public function getBillInputStatus(int $warehousingId)
    {
        $billInputStatus = 0;//单据录入状态(0:未录入 1:部分录入 2:已录入)
        $billGoods = $this->getWarehousingBillGoodsById($warehousingId);
        $goodsInputed = 0;
        foreach ($billGoods as $item) {
            if ($item['goodsInputStatus'] > 0) {
                $goodsInputed += 1;
            }
        }
        if ($goodsInputed > 0) {
            $billInputStatus = 1;
        }
        if ($goodsInputed >= count($billGoods)) {
            $billInputStatus = 2;
        }
        return $billInputStatus;
    }

    /**
     * 入库单商品-保存
     * @param array $paramsInput
     * wst_warehousing_bill_goods表字段
     * @return object $trans
     * @return int
     * */
    public function saveWarehousingBillGoods(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'warehousingId' => null,
            'goodsId' => null,
            'skuId' => null,
            'goodsName' => null,
            'goodsImg' => null,
            'goodsCode' => null,
            'skuSpecStr' => null,
            'describe' => null,
            'unitName' => null,
            'warehousNumTotal' => null,
            'warehouseOkNum' => null,
            'warehousingTime' => null,
            'warehouseStatus' => null,
            'goodsInputStatus' => null,
            'warehousPrice' => null,
            'warehousePriceTotal' => null,
            'goodsRemark' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        $model = new WarehousingBillGoodsModel();
        if (empty($paramsInput['id'])) {
            $saveParams['createTime'] = $datetime;
            $id = $model->add($saveParams);
            if (empty($id)) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $id = $paramsInput['id'];
            $where = array(
                'id' => $id
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
        return (int)$id;
    }

    /**
     * 入库单-单据商品详情-入库商品唯一标识id查找
     * @param int $id 入库商品唯一标识id查找
     * @return array
     * */
    public function getWarehousingBillGoodsDetailById(int $id)
    {
        $where = "id={$id}";
        $model = new WarehousingBillGoodsModel();
        $result = $model->where($where)->lock(true)->find();
        if (empty($result)) {
            return array();
        }
        $warehousingEnum = new WarehousingBillEnum();
        $result['warehousingTime'] = (string)$result['warehousingTime'];
        $result['warehouseOkPriceTotal'] = bc_math($result['warehousPrice'], $result['warehouseOkNum'], 'bcmul', 2);
        $result['warehouseNoNum'] = bc_math($result['warehousNumTotal'], $result['warehouseOkNum'], 'bcsub', 3);
        if ((float)$result['warehouseNoNum'] < 0) {
            $result['warehouseNoNum'] = '0';
        }
        $result['goodsInputStatusName'] = $warehousingEnum::getGoodsInputStatus()[$result['goodsInputStatus']];
        $result['warehouseStatusName'] = $warehousingEnum::getWarehousingStatus()[$result['warehouseStatus']];
        return (array)$result;
    }

    /**
     * 入库单-打印-记录打印次数
     * @param int $warehousingId 入库单id
     * @param object $trans
     * @return bool
     * */
    public function incWarehousingBillPrintNum(int $warehousingId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new WarehousingBillModel();
        $result = $model->where(array('warehousingId' => $warehousingId))->setInc('printNum');
        if (!$result) {
            $dbTrans->rollback();
            return false;
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return true;
    }

}