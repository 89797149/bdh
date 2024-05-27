<?php
/**
 * 成本调整单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-14
 * Time: 18:05
 */

namespace App\Modules\PurchasePriceChangeBill;


use App\Enum\ExceptionCodeEnum;
use App\Enum\PurchasePriceChange\PurchasePriceChangeEnum;
use App\Models\PurchasePriceChangeBillGoodsModel;
use App\Models\PurchasePriceChangeBillModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use Think\Model;

class PurchasePriceChangeBillModule
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
    public function createPurchasePriceChangeBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $loginInfo = $paramsInput['loginInfo'];
        $shopId = $loginInfo['shopId'];
        $goods_list = $paramsInput['goods_list'];
        if (empty($goods_list)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择商品');
        }
        $billGoodsData = array();//单据商品备参
        $goodsModule = new GoodsModule();
        foreach ($goods_list as $goodsDetail) {
            if (empty($goodsDetail['goodsId'])) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误-goodsId异常');
            }
            if ((float)$goodsDetail['nowPurchasePrice'] < 0) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '成本金额不能小于0');
            }
            $goodsId = (int)$goodsDetail['goodsId'];
            $skuId = (int)$goodsDetail['skuId'];
            $goodsField = 'goodsId,goodsName,goodsImg,goodsSpec as `describe`,unit as unitName,goodsStock,goodsUnit as purchase_price,shopId,goodsSn as goodsCode';
            $goodsRow = $goodsModule->getGoodsInfoById($goodsId, $goodsField, 2);
            if (empty($goodsRow)) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
            }
            $billGoodsDataDetail = array(
                'goodsId' => $goodsId,
                'skuId' => $skuId,
                'goodsName' => $goodsRow['goodsName'],
                'goodsStock' => $goodsRow['goodsStock'],
                'goodsImg' => $goodsRow['goodsImg'],
                'goodsCode' => $goodsRow['goodsCode'],
                'describe' => $goodsRow['describe'],
                'unitName' => $goodsRow['unitName'],
                'skuSpecStr' => '',
                'originalPurchasePrice' => $goodsRow['purchase_price'],
                'originalPurchasePriceTotal' => bc_math($goodsRow['purchase_price'], $goodsRow['goodsStock'], 'bcmul', 2),
                'nowPurchasePrice' => $goodsDetail['nowPurchasePrice'],
                'nowPurchasePriceTotal' => bc_math($goodsDetail['nowPurchasePrice'], $goodsRow['goodsStock'], 'bcmul', 2),
                'goodsRemark' => $goodsDetail['goodsRemark'],
            );
            if ($goodsRow['shopId'] != $shopId) {
                $dbTrans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品与门店不匹配');
            }
            if (!empty($skuId)) {
                $skuDetail = $goodsModule->getSkuSystemInfoById($skuId, 2);
                if (empty($skuDetail)) {
                    $dbTrans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', '提交的商品规格信息有误');
                }
                $billGoodsDataDetail['goodsImg'] = $skuDetail['skuGoodsImg'];
                $billGoodsDataDetail['goodsCode'] = $skuDetail['skuBarcode'];
                $billGoodsDataDetail['goodsStock'] = $skuDetail['skuGoodsStock'];
                $billGoodsDataDetail['unitName'] = $skuDetail['unit'];
                $billGoodsDataDetail['skuSpecStr'] = $skuDetail['skuSpecAttrTwo'];
                $billGoodsDataDetail['originalPurchasePrice'] = $skuDetail['purchase_price'];
                $billGoodsDataDetail['originalPurchasePriceTotal'] = bc_math($skuDetail['purchase_price'], $skuDetail['skuGoodsStock'], 'bcmul', 2);
                $billGoodsDataDetail['nowPurchasePriceTotal'] = bc_math($goodsDetail['nowPurchasePrice'], $skuDetail['skuGoodsStock'], 'bcmul', 2);
            }
            $billGoodsDataDetail['diffAmountTotal'] = bc_math($billGoodsDataDetail['nowPurchasePriceTotal'], $billGoodsDataDetail['originalPurchasePriceTotal'], 'bcsub', 2);
            $billGoodsData[] = $billGoodsDataDetail;
        }
        $datetime = date('Y-m-d H:i:s');
        $billData = array(
            'shopId' => $shopId,
            'creatorId' => $loginInfo['user_id'],
            'creatorName' => $loginInfo['user_username'],
            'changeBillRemark' => $paramsInput['changeBillRemark'],
            'goodsKindCount' => count($billGoodsData),
        );
        $changeId = $this->savePurchasePriceChangeBill($billData, $dbTrans);
        if (empty($changeId)) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '单据创建失败');
        }
        foreach ($billGoodsData as &$item) {
            $item['changeId'] = $changeId;
            $item['createTime'] = $datetime;
            $item['updateTime'] = $datetime;
        }
        unset($item);
        $goodsModel = new PurchasePriceChangeBillGoodsModel();
        $addGoodsRes = $goodsModel->addAll($billGoodsData);
        if (!$addGoodsRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '单据商品关联失败');
        }
        $tableActionModule = new TableActionLogModule();
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_price_change_bill',
            'dataId' => $changeId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => 0,
            'remark' => '创建成本调整单据',
        ];
        $logRes = $tableActionModule->addTableActionLog($logParams, $dbTrans);
        if (!$logRes) {
            $dbTrans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "日志记录失败");
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return returnData(true);
    }

    /**
     * 成本调整单-保存
     * @param array $paramsInput
     * -wst_purchase_price_change_bill表字段
     * @param object $trans
     * */
    public function savePurchasePriceChangeBill(array $paramsInput, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $model = new PurchasePriceChangeBillModel();
        $datetime = date('Y-m-d H:i:s');
        $saveParams = array(
            'shopId' => null,
            'billNo' => null,
            'creatorId' => null,
            'creatorName' => null,
            'changeBillRemark' => null,
            'billStatus' => null,
            'examineTime' => null,
            'goodsKindCount' => null,
            'isDelete' => null,
            'updateTime' => $datetime,
        );
        parm_filter($saveParams, $paramsInput);
        if (isset($saveParams['isDelete'])) {
            if ($saveParams['isDelete'] == 1) {
                $saveParams['deleteTime'] = $datetime;
            }
        }
        if (isset($saveParams['billStatus'])) {
            if ($saveParams['billStatus'] == 1) {
                $saveParams['examineTime'] = $datetime;
            }
        }
        if (empty($paramsInput['changeId'])) {
            $saveParams['createTime'] = $datetime;
            $changeId = $model->add($saveParams);
            if (empty($changeId)) {
                $dbTrans->rollback();
                return 0;
            }
            $saveParams = array(
                'billNo' => 'CBTZ' . date('Ymd') . str_pad($changeId, 10, "0", STR_PAD_LEFT),
            );
            $saveRes = $model->where(array('changeId' => $changeId))->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        } else {
            $changeId = $paramsInput['changeId'];
            $saveRes = $model->where(array('changeId' => $changeId))->save($saveParams);
            if (!$saveRes) {
                $dbTrans->rollback();
                return 0;
            }
        }
        if (empty($trans)) {
            $dbTrans->commit();
        }
        return (int)$changeId;
    }

    /**
     * 成本调整单-单据信息-id查找
     * @param int $changeId 单据id
     * @param string $field 表字段
     * @return array
     * */
    public function getPurchasePriceChangeBillDetial(int $changeId, $field = '*')
    {
        $model = new PurchasePriceChangeBillModel();
        $where = array(
            'changeId' => $changeId,
            'isDelete' => 0,
        );
        $result = $model->where($where)->field($field)->find();
        if (empty($result)) {
            return array();
        }
        if (isset($result['billStatus'])) {
            $result['billStatusName'] = PurchasePriceChangeEnum::BILL_STATUS[$result['billStatus']];
        }
        if (empty($result['examineTime'])) {
            $result['examineTime'] = (string)$result['examineTime'];
        }
        return $result;
    }

    /**
     * 成本调整单-商品信息
     * @param int $changeId 单据id
     * @return array
     * */
    public function getPurchasePriceChangeBillGoods(int $changeId)
    {
        $model = new PurchasePriceChangeBillGoodsModel();
        $where = array(
            'changeId' => $changeId,
            'isDelete' => 0,
        );
        $result = $model->where($where)->select();
        foreach ($result as $key => &$item) {
            $item['keyNum'] = $key + 1;
        }
        unset($item);
        return (array)$result;
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
        $shopId = $paramsInput['shopId'];
        $model = new PurchasePriceChangeBillModel();
        $where = " shopId={$shopId} and isDelete=0 ";
        if (!empty($paramsInput['createDateStart'])) {
            $where .= " and createTime >= '{$paramsInput['createDateStart']} 00:00:00' ";
        }
        if (!empty($paramsInput['createDateEnd'])) {
            $where .= " and createTime <= '{$paramsInput['createDateEnd']} 23:59:59' ";
        }
        if (!empty($paramsInput['keywords'])) {
            $where .= " and (billNo like '%{$paramsInput['keywords']}%' or creatorName like '%{$paramsInput['keywords']}%') ";
        }
        $field = 'changeId,billNo,createTime,creatorName,goodsKindCount,billStatus';
        $sql = $model
            ->where($where)
            ->field($field)
            ->order('createTime desc')
            ->buildSql();
        $result = $model->pageQuery($sql, $paramsInput['page'], $paramsInput['pageSize']);
        foreach ($result['root'] as &$item) {
            $item['billStatusName'] = PurchasePriceChangeEnum::getBillStatus()[$item['billStatus']];
        }
        unset($item);
        return $result;
    }

    /**
     * 成本调整单-删除
     * @param int $changeId 单据id
     * @param object $trans
     * @return bool
     * */
    public function delPurchasePriceChangeBill(int $changeId, $trans = null)
    {
        if (empty($trans)) {
            $dbTrans = new Model();
            $dbTrans->startTrans();
        } else {
            $dbTrans = $trans;
        }
        $where = array(
            'changeId' => $changeId
        );
        $saveParams = array(
            'isDelete' => 1,
            'deleteTime' => date('Y-m-d H:i:s'),
        );
        $billModel = new PurchasePriceChangeBillModel();
        $delBillRes = $billModel->where($where)->save($saveParams);
        if (!$delBillRes) {
            $dbTrans->rollback();
            return false;
        }
        $billGoodsModel = new PurchasePriceChangeBillGoodsModel();
        $delBillGoodsRes = $billGoodsModel->where($where)->save($saveParams);
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