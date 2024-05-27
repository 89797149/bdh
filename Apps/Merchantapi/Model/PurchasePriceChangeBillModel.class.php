<?php
/**
 * 成本调整单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-14
 * Time: 17:42
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsModel;
use App\Models\PurchasePriceChangeBillGoodsModel;
use App\Models\SkuGoodsSystemModel;
use App\Modules\Log\TableActionLogModule;
use App\Modules\PurchasePriceChangeBill\PurchasePriceChangeBillModule;
use Think\Model;

class PurchasePriceChangeBillModel extends BaseModel
{
    /**
     * 成本调整单-创建
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -string changeBillRemark 单据备注
     * -array goods_list 商品信息
     * --int goodsId 商品id
     * --int skuId 规格id
     * --float nowPurchasePrice 新成本价
     * --string goodsRemark 商品备注
     * @return array
     * */
    public function addPurchasePriceChangeBill(array $paramsInput)
    {
        $module = new PurchasePriceChangeBillModule();
        $result = $module->createPurchasePriceChangeBill($paramsInput);
        return $result;
    }

    /**
     * 成本调整单-修改
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int changeId 单据id
     * -int examine 是否审核(0:否 1:是)
     * -string changeBillRemark 单据备注
     * -array goods_list 商品信息
     * --int id 成本调整商品关联唯一标识id
     * --float nowPurchasePrice 新成本价
     * --string goodsRemark 商品备注
     * @return array
     * */
    public function updatePurchasePriceChangeBill(array $paramsInput)
    {
        $loginInfo = $paramsInput['loginInfo'];
        $shopId = $loginInfo['shopId'];
        $changeId = $paramsInput['changeId'];
        $goodsList = $paramsInput['goods_list'];
        $module = new PurchasePriceChangeBillModule();
        $detail = $module->getPurchasePriceChangeBillDetial($changeId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息有误');
        }
        if ($detail['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据与门店不匹配');
        }
        if ($detail['billStatus'] != 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已审核的单据不能修改');
        }
        $existGoods = $module->getPurchasePriceChangeBillGoods($changeId);
        $existIdArr = array_column($existGoods, 'id');
        $resetGoods = array();
        foreach ($existGoods as $existGoodsDetail) {
            $resetGoods[$existGoodsDetail['id']] = $existGoodsDetail;
        }
        $saveGoodsParams = array();//单据商品更新备参
        $datetime = date('Y-m-d H:i:s');
        foreach ($goodsList as $goodsDetail) {
            $id = $goodsDetail['id'];
            if (!in_array($id, $existIdArr)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品与单据不匹配');
            }
            if ((float)$goodsDetail['nowPurchasePrice'] < 0) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '成本金额不能小于0');
            }
            $resetGoodsDetail = $resetGoods[$id];
            $saveGoodsParamsDetail = array(
                'id' => $id,
                'nowPurchasePrice' => $goodsDetail['nowPurchasePrice'],
                'nowPurchasePriceTotal' => bc_math($goodsDetail['nowPurchasePrice'], $resetGoodsDetail['goodsStock'], 'bcmul', 2),
                'goodsRemark' => $goodsDetail['goodsRemark'],
                'updateTime' => $datetime
            );
            $saveGoodsParamsDetail['diffAmountTotal'] = bc_math($saveGoodsParamsDetail['nowPurchasePriceTotal'], $resetGoodsDetail['originalPurchasePriceTotal'], 'bcsub', 2);
            $saveGoodsParams[] = $saveGoodsParamsDetail;
        }
        $nowIdArr = array_column($goodsList, 'id');
        $diffIdArr = array_diff($existIdArr, $nowIdArr);
        $trans = new Model();
        $trans->startTrans();
        $billParams = array(
            'changeId' => $changeId,
            'changeBillRemark' => $paramsInput['changeBillRemark'],
            'goodsKindCount' => count($saveGoodsParams),
        );
        if ($paramsInput['examine'] == 1) {
            $billParams['billStatus'] = 1;
        }
        $saveBillRes = $module->savePurchasePriceChangeBill($billParams, $trans);
        if (!$saveBillRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败', '单据信息更新失败');
        }
        $billGoodsModel = new PurchasePriceChangeBillGoodsModel();
        $saveGoodsRes = $billGoodsModel->saveAll($saveGoodsParams, 'wst_purchase_price_change_bill_goods', 'id');
        if (!$saveGoodsRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败', '单据商品更新失败');
        }
        if (!empty($diffIdArr)) {
            $delWhere = array(
                'id' => array('in', $diffIdArr)
            );
            $delParams = array(
                'isDelete' => 1,
                'deleteTime' => $datetime,
            );
            $delRes = $billGoodsModel->where($delWhere)->save($delParams);
            if (!$delRes) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败', '单据商品删除失败');
            }
        }
        $tableActionModule = new TableActionLogModule();
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_price_change_bill',
            'dataId' => $changeId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => $paramsInput['examine'] == 1 ? 1 : 0,
            'remark' => $paramsInput['examine'] == 1 ? '成本调整单据已审核通过' : '修改了成本调整单据信息',
        ];
        $logRes = $tableActionModule->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "日志记录失败");
        }
        if ($paramsInput['examine'] == 1) {
            $nowGoods = $module->getPurchasePriceChangeBillGoods($changeId);
            $goodsModel = new GoodsModel();
            $skuModel = new SkuGoodsSystemModel();
            foreach ($nowGoods as $nowGoodsDetail) {
                $goodsId = $nowGoodsDetail['goodsId'];
                $skuId = $nowGoodsDetail['skuId'];
                if (empty($skuId)) {
                    $saveRes = $goodsModel->where(array('goodsId' => $goodsId))->save(array('goodsUnit' => $nowGoodsDetail['nowPurchasePrice']));
                } else {
                    $saveRes = $skuModel->where(array('skuId' => $skuId))->save(array('purchase_price' => $nowGoodsDetail['nowPurchasePrice']));
                }
                if ($saveRes === false) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "商品成本价更新失败");
                }
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 成本调整单-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -date createDateStart 单据日期区间-开始日期
     * -date createDateEnd 单据日期区间-结束日期
     * -string keywords 关键字(单号/制单人)
     * -int page 页码
     * -int pageSize 每页条数
     * @return array
     * */
    public function getPurchasePriceChangeBillList(array $paramsInput)
    {
        $module = new PurchasePriceChangeBillModule();
        $result = $module->getPurchasePriceChangeBillList($paramsInput);
        return $result;
    }

    /**
     * 成本调整单-详情
     * @param int $changeId 单据id
     * @param int $shopId 门店id
     * @return array
     * */
    public function getPurchasePriceChangeBillDetail(int $changeId, int $shopId)
    {
        $module = new PurchasePriceChangeBillModule();
        $detail = $module->getPurchasePriceChangeBillDetial($changeId);
        if (empty($detail)) {
            return array();
        }
        if ($detail['shopId'] != $shopId) {
            return array();
        }
        $goodsList = $module->getPurchasePriceChangeBillGoods($changeId);
        $detail['goods_list'] = $goodsList;
        $logParams = array(
            'tableName' => 'wst_purchase_price_change_bill',
            'dataId' => $changeId,
        );
        $logField = 'logId,actionUserName,remark,createTime';
        $logList = (new TableActionLogModule())->getLogListByParams($logParams, $logField);
        $detail['log_data'] = $logList;
        return $detail;
    }

    /**
     * 成本调整单-删除
     * @param int $changeId 单据id
     * @param array $loginInfo 登陆者信息
     * @return array
     * */
    public function delPurchasePriceChangeBill(int $changeId, array $loginInfo)
    {
        $module = new PurchasePriceChangeBillModule();
        $detail = $module->getPurchasePriceChangeBillDetial($changeId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据异常');
        }
        if ($detail['shopId'] != $loginInfo['shopId']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据与门店不匹配');
        }
        if ($detail['billStatus'] != 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已审核的单据不能删除');
        }
        $trans = new Model();
        $trans->startTrans();
        $result = $module->delPurchasePriceChangeBill($changeId, $trans);
        if (!$result) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        $tableActionModule = new TableActionLogModule();
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_price_change_bill',
            'dataId' => $changeId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'isDelete',
            'fieldValue' => 1,
            'remark' => '删除了成本调整单据信息',
        ];
        $logRes = $tableActionModule->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "修改失败", "日志记录失败");
        }
        $trans->commit();
        return returnData(true);
    }

}