<?php
/**
 * 采购单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-20
 * Time: 18:29
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Enum\PurchaseBill\PurchaseBillEnum;
use App\Models\PurchaseBillGoodsModel;
use App\Models\PurchaseReturnBillGoodsModel;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\PurchaseBill\PurchaseBillModule;
use App\Modules\WarehousingBill\WarehousingBillModule;
use Think\Model;

class PurchaseBillModel extends BaseModel
{
    /**
     * 采购单-添加
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
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
     * @return array
     * */
    public function addPurchaseBill(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        $paramsInput['billFrom'] = 1;
        $result = $module->createPurchaseBill($paramsInput);
        return $result;
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
     * @return array
     * */
    public function updatePurchaseBill(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        $paramsInput['billFrom'] = 1;
        $result = $module->updatePurchaseBill($paramsInput);
        return $result;
    }

    /**
     * 采购单-列表
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
        $module = new PurchaseBillModule();
        $result = $module->getPurchaseBillList($paramsInput);
        if (!empty($result['root'])) {
            $list = $result['root'];
            foreach ($list as &$item) {
                $purchaseGoodsNumDetial = $module->getPurchaseGoodsNumDetial($item['purchaseId']);
                $item['purchaseGoodsNumTotal'] = $purchaseGoodsNumDetial['purchaseGoodsNumTotal'];//采购商品总个数
                $item['purchasedGoodsNumTotal'] = $purchaseGoodsNumDetial['purchasedGoodsNumTotal'];//已采购商品个数
            }
            unset($item);
            $result['root'] = $list;
        }
        if ($paramsInput['export'] == 1) {
            //导出
            $this->exportPurchaseList($result);
        }
        return returnData($result);
    }

    /**
     * 采购单-列表-导出
     * @param array $result
     * */
    public function exportPurchaseList(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/purchase_list.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 1;
        foreach ($result as $detail) {
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['billNo']);//单号
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['plannedDeliveryDate']);//计划交货日期
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['billTypeName']);//采购类型
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['goodsName']);//商品名称
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['skuSpecStr']);//规格
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['unitName']);//单位
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['describe']);//描述
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detail['purchaseStatusName']);//状态
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detail['purchasePrice']);//采购价
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detail['purchaseTotalNum']);//采购数量
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $detail['purchaseOkNum']);//收货数量
            $purchaseNoNum = bc_math($detail['purchaseTotalNum'], $detail['purchaseOkNum'], 'bcsub', 3);
            if ((float)$purchaseNoNum <= 0) {
                $purchaseNoNum = 0;
            }
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $purchaseNoNum);//未收货数量
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $detail['deliveryAmount']);//收货金额
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $detail['goodsRemark']);//商品备注
        }
        $savefileName = '采购单列表导出_' . date('YmdHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 采购单-详情
     * @param int $purchaseId 采购单id
     * @param string $keywords 商品关键字
     * @param int $export 是否导出(0:不导出 1:导出)
     * @return array
     * */
    public function getPurchaseBillDetail(int $purchaseId, $keywords = '', $export = 0)
    {
        $module = new PurchaseBillModule();
        $billField = 'purchaseId,billNo,billType,billFrom,supplierConfirm,purchaseStatus,creatorName,plannedDeliveryDate,billRemark,billAmount,deliveryAmount,createTime,creatorName,purchaserId,supplierId,printNum,returnBillStatus';
        $billData = $module->getPurchaseDetailById($purchaseId, $billField);
        if (empty($billData)) {
            return array();
        }
        $purchaseGoodsNumDetail = $module->getPurchaseGoodsNumDetial($purchaseId);
        $billData['purchaseGoodsNumTotal'] = $purchaseGoodsNumDetail['purchaseGoodsNumTotal'];
        $billData['purchasedGoodsNumTotal'] = $purchaseGoodsNumDetail['purchasedGoodsNumTotal'];
        $goodsField = 'relation.id,relation.goodsId,relation.skuId,relation.goodsName,relation.goodsImg,relation.unitName,relation.describe,relation.skuSpecStr,relation.purchaseStatus,relation.purchasePrice,relation.purchasePriceTotal,relation.purchaseTotalNum,relation.purchaseOkNum,relation.goodsRemark,relation.warehousePrice,relation.warehouseOkNum,relation.warehousePrice,relation.returnOKGoodsNum,relation.returnGoodsStatus';
        $goodsData = $module->getPurchaseGoodsList($purchaseId, $goodsField, $keywords);
        foreach ($goodsData as &$item) {
            $item['returnGoodsNum'] = bc_math($item['warehouseOkNum'], $item['returnOKGoodsNum'], 'bcsub', 3);//应退货数量
            if ((float)$item['returnGoodsNum'] < 0) {
                $item['returnGoodsNum'] = '0.000';
            }
            $item['returnGoodsPrice'] = $item['purchasePrice'];//退货单价
            $item['returnGoodsPriceTotal'] = bc_math($item['returnGoodsPrice'], $item['returnGoodsNum'], 'bcmul', 2);//应退货商品金额小计
            $item['returnGoodsRemark'] = '';//退货商品备注
        }
        unset($item);
        $purEnum = new PurchaseBillEnum();
        $billData['returnBillRemark'] = '';//采购退单备注
        $billData['returnBillAmount'] = array_sum(array_column($goodsData, 'returnGoodsPriceTotal'));//采购退单金额合计
        $billData['returnBillAmount'] = formatAmount($billData['returnBillAmount']);
        $billData['goods_data'] = $goodsData;
        $billData['purchaseStatusName'] = $purEnum->getPurchaseStatus($billData['purchaseStatus']);
        $billData['supplierConfirmName'] = $purEnum->getSupplierConfirm($billData['supplierConfirm']);
        $billData['billTypeName'] = $purEnum->getBillType($billData['billType']);
        $billData['billFromName'] = $purEnum->getBillFrom($billData['billFrom']);
        $billData['purchaserOrSupplier'] = $module->getPurchaserOrSupplier($purchaseId);
        $billData['log_data'] = $module->getPurchaseLogById($purchaseId);
        if ($export == 1) {
            $this->exportPurchaseDetial($billData);
        }
        return $billData;
    }

    /**
     * 采购单-详情-导出
     * @param array $result
     * */
    public function exportPurchaseDetial(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/purchase_detail.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $objPHPExcel->getActiveSheet()->setCellValue('C' . 2, $result['billNo']);//供应商名称
        $objPHPExcel->getActiveSheet()->setCellValue('H' . 2, $result['createTime']);//制单时间
        $keyTag = 4;
        $goodsData = $result['goods_data'];
        foreach ($goodsData as $detail) {
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['keyNum']);//序号
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['goodsName']);//商品名称
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['skuSpecStr']);//规格
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['describe']);//描述
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['unitName']);//单位
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['purchaseTotalNum']);//采购数量
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['purchaseOkNum']);//收货数量
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detail['purchaseNoNum']);//未收货数量
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detail['purchasePrice']);//单价
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detail['deliveryAmount']);//收货金额
            $objPHPExcel->getActiveSheet()->mergeCells("K{$keyTag}:M{$keyTag}");
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $detail['goodsRemark']);//商品备注
        }
        $bottomRow = count($goodsData) + 5;
        $objPHPExcel->getActiveSheet()->mergeCells("A{$bottomRow}:M{$bottomRow}");
        $objPHPExcel->getActiveSheet()->setCellValue("A{$bottomRow}", "备注：{$result['billRemark']}");
        $objPHPExcel->getActiveSheet()->mergeCells("A" . ($bottomRow + 1) . ":E" . ($bottomRow + 1));
        $objPHPExcel->getActiveSheet()->setCellValue("A" . ($bottomRow + 1), "采购员/供应商：{$result['purchaserOrSupplier']}");
        $objPHPExcel->getActiveSheet()->mergeCells("F" . ($bottomRow + 1) . ":M" . ($bottomRow + 1));
        $objPHPExcel->getActiveSheet()->setCellValue("F" . ($bottomRow + 1), "制表人：{$result['creatorName']}");
        $savefileName = '采购单详情导出_' . $result['billNo'];
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 采购单-删除
     * @param array $loginInfo 登陆者
     * @param array $purchaseIdArr 采购单id
     * @return array
     * */
    public function delPurchaseBill(array $loginInfo, array $purchaseIdArr)
    {
        $module = new PurchaseBillModule();
        foreach ($purchaseIdArr as $purchaseId) {
            $detail = $module->getPurchaseDetailById($purchaseId, 'billNo,purchaseStatus,supplierConfirm,billType');
            $billNo = (string)$detail['billNo'];
            if ($detail['purchaseStatus'] > 0) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$billNo}已收货，不能删除");
            }
            if ($detail['billType'] == 2 && $detail['supplierConfirm'] == 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$billNo}供应商已确认供货，不能删除 ");
            }
        }
        $trans = new Model();
        $trans->startTrans();
        $result = $module->delPurchaseBill($purchaseIdArr, $trans);
        if (!$result) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        $tableActionLogModule = new TableActionLogModule();
        foreach ($purchaseIdArr as $purchaseId) {
            $logParams = [//写入状态变动记录表
                'tableName' => 'wst_purchase_bill',
                'dataId' => $purchaseId,
                'actionUserId' => $loginInfo['user_id'],
                'actionUserName' => $loginInfo['user_username'],
                'fieldName' => 'isDelete',
                'fieldValue' => 1,
                'remark' => '删除了采购单',
            ];
            $logRes = $tableActionLogModule->addTableActionLog($logParams, $trans);
            if (!$logRes) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "删除失败", "单据日志记录失败");
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 采购单-关闭
     * @param array $loginInfo 登陆者
     * @param array $purchaseIdArr 采购单id
     * @return array
     * */
    public function closePurchaseBill(array $loginInfo, array $purchaseIdArr)
    {
        $module = new PurchaseBillModule();
        foreach ($purchaseIdArr as $purchaseId) {
            $detail = $module->getPurchaseDetailById($purchaseId, 'billNo,purchaseStatus,supplierConfirm,billType');
            $billNo = (string)$detail['billNo'];
            if ($detail['purchaseStatus'] > 0) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$billNo}已收货，不能关闭");
            }
            if ($detail['billType'] == 2 && $detail['supplierConfirm'] == 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "{$billNo}供应商已确认供货，不能关闭");
            }
        }
        $trans = new Model();
        $trans->startTrans();
        $result = $module->closePurchaseBill($purchaseIdArr, $trans);
        if (!$result) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '关闭失败');
        }
        $tableActionLogModule = new TableActionLogModule();
        foreach ($purchaseIdArr as $purchaseId) {
            $logParams = [//写入状态变动记录表
                'tableName' => 'wst_purchase_bill',
                'dataId' => $purchaseId,
                'actionUserId' => $loginInfo['user_id'],
                'actionUserName' => $loginInfo['user_username'],
                'fieldName' => 'isDelete',
                'fieldValue' => 1,
                'remark' => '关闭了采购单',
            ];
            $logRes = $tableActionLogModule->addTableActionLog($logParams, $trans);
            if (!$logRes) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "关闭失败", "单据日志记录失败");
            }
        }
        $trans->commit();
        return returnData(true);
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
     * @return array
     * */
    public function deliveryPurchaseBill(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        $result = $module->deliveryPurchaseBill($paramsInput);
        return $result;
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
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 是否导出(0:否 1:是)
     * @return array
     * */
    public function getOrderGoodsPurchaseList(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        $paramsInput['isNeedMerge'] = 1;
        $result = $module->getOrderGoodsPurchaseList($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportOrderGoodsPurchaseList($result);
        }
        return $result;
    }

    /**
     * 订单商品采购-商品列表-导出
     * @param array $result
     * */
    public function exportOrderGoodsPurchaseList(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/order_goods_purchase.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 2;
        $goodsRowNumber = 0;
        foreach ($result as $detail) {
            ++$goodsRowNumber;
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $goodsRowNumber);//序号
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['shopCat1Name'] . '/' . $detail['shopCat2Name']);//商品店铺分类
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['goodsName']);//商品名称
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['purchasePrice']);//价格
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['skuSpecStrTwo']);//规格
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['purchaseTotalNum']);//采购数量
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['smartRemark']);//智能备注
        }
        $savefileName = '非常规订单商品导出' . date('YmdHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 订单商品采购-删除商品
     * @param int $goodsId 商品id
     * @param int $skuId 规格id
     * @param array $purchaseGoodsWhere 一键采购页的搜索提哦啊见
     * */
    public function delOrderGoodsPurchase(int $goodsId, int $skuId, array $purchaseGoodsWhere)
    {
        $module = new PurchaseBillModule();
        $reqParams = $purchaseGoodsWhere;
        $reqParams['isNeedMerge'] = 0;
        $reqParams['onekeyPurchase'] = 1;
        $reqParams['goodsId'] = $goodsId;
        $reqParams['skuId'] = $skuId;
        $purchaseGoodsList = $module->getOrderGoodsPurchaseList($reqParams);
        if (empty($purchaseGoodsList)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无相关数据');
        }
        $trans = new Model();
        $trans->startTrans();
        foreach ($purchaseGoodsList as $item) {
            $orderId = $item['orderId'];
            $goodsId = $item['goodsId'];
            $skuId = $item['skuId'];
            $res = $module->delPurchaseGoodsByParams($orderId, $goodsId, $skuId, $trans);
            if (!$res) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 采购单-打印-记录打印次数
     * @param array $loginInfo 登陆者信息
     * @param int $purchaseId 采购单id
     * @return array
     * */
    public function incPurchaseBillPrintNum(array $loginInfo, int $purchaseId)
    {
        $module = new PurchaseBillModule();
        $detail = $module->getPurchaseDetailById($purchaseId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息异常');
        }
        if ($detail['shopId'] != $loginInfo['shopId']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息和门店不匹配');
        }
        $trans = new Model();
        $trans->startTrans();
        $incRes = $module->incPurchaseBillPrintNum($purchaseId, $trans);
        if (!$incRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '打印记录失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_bill',
            'dataId' => $purchaseId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'purchaseStatus',
            'fieldValue' => $detail['purchaseStatus'],
            'remark' => "执行打印操作",
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "入库失败", "单据日志记录失败");
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 采购退回-验证采购单数据是否可用
     * @param int $purchaseId 采购单id
     * @param int $shopId 门店id
     * @return array
     * */
    public function verificationPurchaseBill(int $purchaseId, int $shopId)
    {
        $module = new PurchaseBillModule();
        $billDetail = $module->getPurchaseDetailById($purchaseId);
        if (empty($billDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        if ($billDetail['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据和门店不匹配');
        }
        $warehousingId = $module->getPurchaseBillRelationWarehousingId($purchaseId);
        if (empty($warehousingId)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购审核入库之后才能执行该操作');
        }
        $warehousingModule = new WarehousingBillModule();
        $warehousingBillDetail = $warehousingModule->getWarehousingBillDetailById($warehousingId);
        if ($warehousingBillDetail['warehousingStatus'] != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '采购审核入库之后才能执行该操作');
        }
        return returnData(true);
    }

    /**
     * 采购退回-创建采购退回单据
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int is_examine_submit 审核提交(0:提交不审核 1:提交并审核)
     * -int purchaseId 采购单id
     * -int returnBillRemark 采购退单备注
     * -array goods_list 商品数据
     * --int id 采购商品关联唯一id标识
     * --float returnGoodsNum 退货数量
     * --float returnGoodsPrice 退货单价
     * --string returnGoodsRemark 退货商品备注
     * @return array
     * */
    public function addReturnPurchaseBill(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        $isExamineSubmit = $paramsInput['is_examine_submit'];
        $loginInfo = $paramsInput['loginInfo'];
        $shopId = $loginInfo['shopId'];
        $purchaseId = $paramsInput['purchaseId'];
        $verificationPurchaseRes = $this->verificationPurchaseBill($purchaseId, $shopId);
        if ($verificationPurchaseRes['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', $verificationPurchaseRes['msg']);
        }
        $goodsList = $paramsInput['goods_list'];
        if (empty($goodsList)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要退货的商品');
        }
        $purchaseGoods = $module->getPurchaseGoodsList($purchaseId);
        $resetPurGoods = array();
        $purGoodsIdArr = array();
        foreach ($purchaseGoods as $purGoodsVal) {
            $resetPurGoods[$purGoodsVal['id']] = $purGoodsVal;
            $purGoodsIdArr[] = (int)$purGoodsVal['id'];
        }
        $exWarehouseGoods = array();//出库商品备参
        $purReturnGoods = array();//退货商品备参
        $datetime = date('Y-m-d H:i:s');
        $trans = new Model();
        $trans->startTrans();
        $goodsModule = new GoodsModule();
        foreach ($goodsList as $item) {
            $id = (int)$item['id'];
            if (!in_array($id, $purGoodsIdArr)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货商品和采购单不匹配');
            }
            $purGoodsDetail = $resetPurGoods[$id];
            $goodsName = $purGoodsDetail['goodsName'];
            $returnGoodsNum = (float)$item['returnGoodsNum'];
            if ($returnGoodsNum <= 0) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}请输入有效的退货数量");
            }
            $purchaseGoodsReturnNum = (float)bc_math($purGoodsDetail['returnOKGoodsNum'], $returnGoodsNum, 'bcadd', 3);//已退货数量+本次退货数量
            if ($purchaseGoodsReturnNum > (float)$purGoodsDetail['warehouseOkNum']) {
                $trans->rollback();
                $maxReturnNum = (float)bc_math($purGoodsDetail['warehouseOkNum'], $purGoodsDetail['returnOKGoodsNum'], 'bcsub', 3);
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}本次最多允许退货数量：{$maxReturnNum}");
            }
            $purReturnGoods[] = array(
                'goodsId' => $purGoodsDetail['goodsId'],
                'skuId' => $purGoodsDetail['skuId'],
                'goodsName' => $purGoodsDetail['goodsName'],
                'goodsImg' => $purGoodsDetail['goodsImg'],
                'unitName' => $purGoodsDetail['unitName'],
                'skuSpecStr' => $purGoodsDetail['skuSpecStr'],
                'returnGoodsRemark' => $item['returnGoodsRemark'],
                'purchasePrice' => $purGoodsDetail['purchasePrice'],
                'warehouseOkNum' => $purGoodsDetail['warehouseOkNum'],
                'warehousePrice' => $purGoodsDetail['warehousePrice'],
                'returnGoodsNum' => $returnGoodsNum,
                'returnGoodsPrice' => $item['returnGoodsPrice'],
                'returnGoodsPriceTotal' => bc_math($returnGoodsNum, $item['returnGoodsPrice'], 'bcmul', 2),
                'createTime' => $datetime,
                'updateTime' => $datetime,
            );
            if ($isExamineSubmit == 1) {//确认审核,更新采购单商品信息和出库单信息
                $purchaseGoodsParams = array(
                    'id' => $id,
                    'returnGoodsStatus' => 0,
                    'returnOKGoodsNum' => (float)bc_math($purGoodsDetail['returnOKGoodsNum'], $returnGoodsNum, 'bcadd', 3),
                );
                if ($purchaseGoodsParams['returnOKGoodsNum'] > 0) {
                    $purchaseGoodsParams['returnGoodsStatus'] = 1;
                }
                if ($purchaseGoodsParams['returnOKGoodsNum'] >= (float)$purGoodsDetail['warehouseOkNum']) {
                    $purchaseGoodsParams['returnGoodsStatus'] = 2;
                }
                $savePurchaseGoodsRes = $module->savePurchaseBillGoods($purchaseGoodsParams, $trans);
                if (empty($savePurchaseGoodsRes)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "商品{$goodsName}采购信息更新失败");
                }
                $decGoodsStockRes = $goodsModule->deductionGoodsStock($purGoodsDetail['goodsId'], $purGoodsDetail['skuId'], $returnGoodsNum, 1, 1, $trans);
                if ($decGoodsStockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，商品{$goodsName}库房库存不足", "商品{$goodsName}库房库存更新失败");
                }
                $exWarehouseGoods[] = array(//出库单商品信息
                    'goods_id' => $purGoodsDetail['goodsId'],
                    'sku_id' => $purGoodsDetail['skuId'],
                    'nums' => $returnGoodsNum,
                    'unit_price' => $item['returnGoodsPrice'],
                    'actual_delivery_quantity' => $returnGoodsNum,
                    'goodsRemark' => (string)$item['returnGoodsRemark'],
                    'remark' => $purGoodsDetail['describe'],
                    'goods_unit' => $purGoodsDetail['unitName'],
                    'goods_name' => $goodsName,
                    'goods_specs_string' => $purGoodsDetail['skuSpecStr'],
                );
            }
        }
        $returnBillParams = array(
            'shopId' => $shopId,
            'purchaseId' => $purchaseId,
            'creatorId' => $loginInfo['user_id'],
            'creatorName' => $loginInfo['user_username'],
            'returnBillRemark' => $paramsInput['returnBillRemark'],
            'returnBillAmount' => array_sum(array_column($purReturnGoods, 'returnGoodsPriceTotal')),
        );
        if ($isExamineSubmit == 1) {
            $returnBillParams['billStatus'] = 1;
        }
        $returnId = $module->savePurchaseReturnBill($returnBillParams, $trans);
        if (empty($returnId)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，采购退货单创建失败");
        }
        foreach ($purReturnGoods as &$item) {
            $item['returnId'] = $returnId;
        }
        unset($item);
        $returnGoodsModel = new PurchaseReturnBillGoodsModel();
        $addRes = $returnGoodsModel->addAll($purReturnGoods);
        if (!$addRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "采购退货单商品关联失败");
        }
        if ($isExamineSubmit == 1) {//创建出库单
            $exWarehouseOrderModule = new ExWarehouseOrderModule();
            $returnBillDetail = $module->getPurchaseReturnBillDetailById($returnId);
            $exWarehouseBillParams = array(
                'pagetype' => 4,
                'shopId' => $shopId,
                'user_id' => $loginInfo['user_id'],
                'user_name' => $loginInfo['user_username'],
                'remark' => '',
                'relation_order_id' => $returnId,
                'relation_order_number' => $returnBillDetail['billNo'],
                'goods_data' => $exWarehouseGoods,
            );
            $exRes = $exWarehouseOrderModule->addExWarehouseOrder($exWarehouseBillParams, $trans);
            if ($exRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "出库单创建失败");
            }
        }
        $autoUpdatePurchaseBillStatusRes = $module->autoUpdatePurchaseBillStatus($purchaseId, $trans);
        if (!$autoUpdatePurchaseBillStatusRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "采购单退单状态更新失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_return_bill',
            'dataId' => $returnId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => 0,
            'remark' => '创建采购退货单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '日志记录失败');
        }
        if ($isExamineSubmit == 1) {
            $logParams = [//写入状态变动记录表
                'tableName' => 'wst_purchase_return_bill',
                'dataId' => $returnId,
                'actionUserId' => $loginInfo['user_id'],
                'actionUserName' => $loginInfo['user_username'],
                'fieldName' => 'billStatus',
                'fieldValue' => 1,
                'remark' => '采购退货单已审核',
            ];
            $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
            if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '日志记录失败');
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 采购退回-修改采购退回单据
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int is_examine_submit 审核提交(0:提交不审核 1:提交并审核)
     * -int returnId 采购退货单id
     * -int returnBillRemark 采购退单备注
     * -array goods_list 商品数据
     * --int id 采购商品关联唯一id标识
     * --float returnGoodsNum 退货数量
     * --float returnGoodsPrice 退货单价
     * --string returnGoodsRemark 退货商品备注
     * @return array
     * */
    public function updateReturnPurchaseBill(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        $isExamineSubmit = $paramsInput['is_examine_submit'];
        $loginInfo = $paramsInput['loginInfo'];
        $shopId = $loginInfo['shopId'];
        $returnId = $paramsInput['returnId'];
        $goodsList = $paramsInput['goods_list'];
        if (empty($goodsList)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择需要退货的商品');
        }
        $returnBillDetail = $module->getPurchaseReturnBillDetailById($returnId);
        if (empty($returnBillDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据有误');
        }
        if ($returnBillDetail['billStatus'] != 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '只有待审核的单据才能修改');
        }
        if ($returnBillDetail['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '门店和单据信息不匹配');
        }
        $returnGoodsList = $module->getPurchaseReturnBillGoodsList($returnId);
        $returnGoodsIdArr = array_column($returnGoodsList, 'id');
        $purchaseId = $returnBillDetail['purchaseId'];
        $purchaseGoods = $module->getPurchaseGoodsList($purchaseId);
        $resetPurGoods = array();
        foreach ($returnGoodsList as &$returnGoodsVal) {
            foreach ($purchaseGoods as $purchaseGoodsVal) {
                if ($purchaseGoodsVal['goodsId'] == $returnGoodsVal['goodsId'] && $purchaseGoodsVal['skuId'] == $returnGoodsVal['skuId']) {
                    $returnGoodsVal['purchaseGoodsId'] = $purchaseGoodsVal['id'];
                    $returnGoodsVal['returnOKGoodsNum'] = $purchaseGoodsVal['returnOKGoodsNum'];
                    $returnGoodsVal['warehouseOkNum'] = $purchaseGoodsVal['warehouseOkNum'];
                    $resetPurGoods[$returnGoodsVal['id']] = $returnGoodsVal;
                }
            }
        }
        unset($returnGoodsVal);
        $currentReturnGoodsIdArr = array_column($goodsList, 'id');
        $diffIdArr = array_diff($returnGoodsIdArr, $currentReturnGoodsIdArr);
        $exWarehouseGoods = array();//出库商品备参
        $purReturnGoods = array();//退货商品备参
        $trans = new Model();
        $trans->startTrans();
        $goodsModule = new GoodsModule();
        $datetime = date('Y-m-d H:i:s');
        foreach ($goodsList as $item) {
            $id = (int)$item['id'];
            if (!in_array($id, $returnGoodsIdArr)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '退货商品和退货单不匹配');
            }
            $purGoodsDetail = $resetPurGoods[$id];
            $goodsName = $purGoodsDetail['goodsName'];
            $returnGoodsNum = (float)$item['returnGoodsNum'];
            if ($returnGoodsNum <= 0) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}请输入有效的退货数量");
            }
            $purchaseGoodsReturnNum = (float)bc_math($purGoodsDetail['returnOKGoodsNum'], $returnGoodsNum, 'bcadd', 3);//已退货数量+本次退货数量
            if ($purchaseGoodsReturnNum > (float)$purGoodsDetail['warehouseOkNum']) {
                $trans->rollback();
                $maxReturnNum = (float)bc_math($purGoodsDetail['warehouseOkNum'], $purGoodsDetail['returnOKGoodsNum'], 'bcsub', 3);
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}本次最多允许退货数量：{$maxReturnNum}");
            }
            $purReturnGoods[] = array(
                'id' => $purGoodsDetail['id'],
                'returnGoodsNum' => $returnGoodsNum,
                'returnGoodsPrice' => $item['returnGoodsPrice'],
                'returnGoodsPriceTotal' => bc_math($returnGoodsNum, $item['returnGoodsPrice'], 'bcmul', 2),
                'updateTime' => $datetime,
            );
            if ($isExamineSubmit == 1) {//确认审核,更新采购单商品信息和出库单信息
                $purchaseGoodsParams = array(
                    'id' => $item['purchaseGoodsId'],
                    'returnGoodsStatus' => 0,
                    'returnOKGoodsNum' => (float)bc_math($purGoodsDetail['returnOKGoodsNum'], $returnGoodsNum, 'bcadd', 3),
                );
                if ($purchaseGoodsParams['returnOKGoodsNum'] > 0) {
                    $purchaseGoodsParams['returnGoodsStatus'] = 1;
                }
                if ($purchaseGoodsParams['returnOKGoodsNum'] >= (float)$purGoodsDetail['warehouseOkNum']) {
                    $purchaseGoodsParams['returnGoodsStatus'] = 2;
                }
                $savePurchaseGoodsRes = $module->savePurchaseBillGoods($purchaseGoodsParams, $trans);
                if (empty($savePurchaseGoodsRes)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "商品{$goodsName}采购信息更新失败");
                }
                $decGoodsStockRes = $goodsModule->deductionGoodsStock($purGoodsDetail['goodsId'], $purGoodsDetail['skuId'], $returnGoodsNum, 1, 1, $trans);
                if ($decGoodsStockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，商品{$goodsName}库房库存不足", "商品{$goodsName}库房库存更新失败");
                }
                $exWarehouseGoods[] = array(//出库单商品信息
                    'goods_id' => $purGoodsDetail['goodsId'],
                    'sku_id' => $purGoodsDetail['skuId'],
                    'nums' => $returnGoodsNum,
                    'unit_price' => $item['returnGoodsPrice'],
                    'actual_delivery_quantity' => $returnGoodsNum,
                    'goodsRemark' => (string)$item['returnGoodsRemark'],
                    'remark' => $purGoodsDetail['describe'],
                    'goods_unit' => $purGoodsDetail['unitName'],
                    'goods_name' => $goodsName,
                    'goods_specs_string' => $purGoodsDetail['skuSpecStr'],
                );
            }
        }
        $returnBillParams = array(
            'returnId' => $returnId,
            'returnBillRemark' => $paramsInput['returnBillRemark'],
            'returnBillAmount' => array_sum(array_column($purReturnGoods, 'returnGoodsPriceTotal')),
        );
        if ($isExamineSubmit == 1) {
            $returnBillParams['billStatus'] = 1;
        }
        $returnId = $module->savePurchaseReturnBill($returnBillParams, $trans);
        if (empty($returnId)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，采购退货单创建失败");
        }
        $returnGoodsModel = new PurchaseReturnBillGoodsModel();
        $saveRes = $returnGoodsModel->saveAll($purReturnGoods, 'wst_purchase_return_bill_goods', 'id');
        if (!$saveRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "采购退货单商品更新失败");
        }
        if ($isExamineSubmit == 1) {//创建出库单
            $exWarehouseOrderModule = new ExWarehouseOrderModule();
            $returnBillDetail = $module->getPurchaseReturnBillDetailById($returnId);
            $exWarehouseBillParams = array(
                'pagetype' => 4,
                'shopId' => $shopId,
                'user_id' => $loginInfo['user_id'],
                'user_name' => $loginInfo['user_username'],
                'remark' => '',
                'relation_order_id' => $returnId,
                'relation_order_number' => $returnBillDetail['billNo'],
                'goods_data' => $exWarehouseGoods,
            );
            $exRes = $exWarehouseOrderModule->addExWarehouseOrder($exWarehouseBillParams, $trans);
            if ($exRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "出库单创建失败");
            }
        }
        $autoUpdatePurchaseBillStatusRes = $module->autoUpdatePurchaseBillStatus($purchaseId, $trans);
        if (!$autoUpdatePurchaseBillStatusRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "采购单退单状态更新失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_return_bill',
            'dataId' => $returnId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => 0,
            'remark' => '修改采购退货单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '日志记录失败');
        }
        if (!empty($diffIdArr)) {
            $delRes = $module->delPurchaseReturnGoodsById($diffIdArr, $trans);
            if (!$delRes) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '采购退货商品更新失败');
            }
        }
        if ($isExamineSubmit == 1) {
            $logParams = [//写入状态变动记录表
                'tableName' => 'wst_purchase_return_bill',
                'dataId' => $returnId,
                'actionUserId' => $loginInfo['user_id'],
                'actionUserName' => $loginInfo['user_username'],
                'fieldName' => 'billStatus',
                'fieldValue' => 1,
                'remark' => '采购退货单已审核',
            ];
            $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
            if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '日志记录失败');
            }
        }
        $trans->commit();
        return returnData(true);
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
     * -string export 导出(0:不导出 1:导出)
     * -int page 页码
     * -int pageSize 每页条数
     * @return array
     * */
    public function getPurchaseReturnBillList(array $paramsInput)
    {
        $module = new PurchaseBillModule();
        if ($paramsInput['export'] == 1) {
            $paramsInput['usePage'] = 0;
        }
        $result = $module->getPurchaseReturnBillList($paramsInput);
        if ($paramsInput['export'] == 1) {
            foreach ($result as &$item) {
                $item['goods_list'] = $module->getPurchaseReturnBillGoodsList($item['returnId']);
            }
            unset($item);
            $this->exportPurchaseReturnList($result);

        }
        return $result;
    }

    /**
     * 采购退货单-列表-导出
     * @param array $result
     * */
    public function exportPurchaseReturnList(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/purchase_return_bill_list.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 1;
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),
            ),
        );
        foreach ($result as $detail) {
            foreach ($detail['goods_list'] as $goodsDetail) {
                $keyTag++;
                $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:O{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['billNo']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['purchaseBillNo']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['billStatusName']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['creatorName']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['createTime']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['returnBillAmount']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['purchaserOrSupplier']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $goodsDetail['goodsName']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $goodsDetail['skuSpecStr']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $goodsDetail['unitName']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $goodsDetail['describe']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $goodsDetail['returnGoodsNum']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $goodsDetail['returnGoodsPrice']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $goodsDetail['returnGoodsPriceTotal']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $goodsDetail['returnGoodsRemark']);
            }
        }
        $savefileName = '采购退货单列表导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 采购退货单-详情
     * @param int $returnId 采购退回单id
     * @param int $export 导出(0:不导出 1:导出)
     * @param int $shopId 门店id
     * @return array
     * */
    public function getPurchaseReturnBillDetail(int $returnId, int $export, int $shopId)
    {
        $module = new PurchaseBillModule();
        $result = $module->getPurchaseReturnBillDetailById($returnId);
        if (empty($result)) {
            return array();
        }
        if ($result['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, "error", "门店和单据信息不匹配");
        }
        $result['goods_list'] = $module->getPurchaseReturnBillGoodsList($returnId);
        if ($export == 1) {
            $this->exportPurchaseReturnDetail($result);
        }
        $result['log_data'] = (new TableActionLogModule())->getLogListByParams(array('tableName' => 'wst_purchase_return_bill', 'dataId' => $returnId), 'logId,actionUserName,remark,createTime');
        return $result;
    }

    /**
     * 采购退货单-详情-导出
     * @param array $result
     * */
    public function exportPurchaseReturnDetail(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/purchase_return_bill_detail.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 5;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 2, "退货单号:{$result['billNo']}");
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 2, "源采购单号:{$result['purchaseBillNo']}");
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 3, "制单人:{$result['creatorName']}");
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 3, "退货金额:{$result['returnBillAmount']}");
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 4, "审核状态:{$result['billStatusName']}");
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 4, "单据日期:{$result['createTime']}");
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),
            ),
        );
        foreach ($result["goods_list"] as $detial) {
            $keyTag++;
            $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:H{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detial['keyNum']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detial['goodsName']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detial['skuSpecStr']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detial['unitName']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detial['returnGoodsNum']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detial['returnGoodsPrice']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detial['returnGoodsPriceTotal']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detial['returnGoodsRemark']);
        }
        $savefileName = '采购退货单详情导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 采购退货单-关闭
     * @param int $returnId 采购退货单id
     * @param array $loginInfo 登陆者信息
     * @return array
     * */
    public function closePurchaseReturnBill(int $returnId, array $loginInfo)
    {
        $shopId = $loginInfo['shopId'];
        $module = new PurchaseBillModule();
        $detail = $module->getPurchaseReturnBillDetailById($returnId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        if ($detail['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '门店和单据信息不匹配');
        }
        if ($detail['billStatus'] != 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '只有未审核的单据才可以关闭');
        }
        $trans = M();
        $trans->startTrans();
        $paramsInput = array(
            'returnId' => $returnId,
            'billStatus' => -1,
        );
        $saveRes = $module->savePurchaseReturnBill($paramsInput, $trans);
        if (!$saveRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '单据信息更新失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_bill',
            'dataId' => $returnId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => -1,
            'remark' => '关闭了采购退货单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "关闭失败", "单据日志记录失败");
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 采购退货单-删除
     * @param int $returnId 采购退货单id
     * @param array $loginInfo 登陆者信息
     * @return array
     * */
    public function delPurchaseReturnBill(int $returnId, array $loginInfo)
    {
        $shopId = $loginInfo['shopId'];
        $module = new PurchaseBillModule();
        $detail = $module->getPurchaseReturnBillDetailById($returnId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        if ($detail['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '门店和单据信息不匹配');
        }
        if (!in_array($detail['billStatus'], array(-1, 0))) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '只有未审核或者已关闭的单据才可以删除');
        }
        $trans = M();
        $trans->startTrans();
        $delRes = $module->delPurchaseReturnBill($returnId, $trans);
        if (!$delRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '单据信息删除失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_bill',
            'dataId' => $returnId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'isDelete',
            'fieldValue' => 1,
            'remark' => '删除了采购退货单',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "单据日志记录失败");
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 采购退货单-审核
     * @param int $returnId 采购退货单id
     * @param array $loginInfo 登陆者信息
     * @return array
     * */
    public function examinePurchaseReturnBill(int $returnId, array $loginInfo)
    {
        $shopId = $loginInfo['shopId'];
        $module = new PurchaseBillModule();
        $detail = $module->getPurchaseReturnBillDetailById($returnId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        if ($detail['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '门店和单据信息不匹配');
        }
        if ($detail['billStatus'] != 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '只有未审核的单据才可以执行该操作');
        }
        $shopId = $loginInfo['shopId'];
        $returnGoodsList = $module->getPurchaseReturnBillGoodsList($returnId);
        $purchaseId = $detail['purchaseId'];
        $purchaseGoods = $module->getPurchaseGoodsList($purchaseId);
        $resetPurGoods = array();
        foreach ($returnGoodsList as &$returnGoodsVal) {
            foreach ($purchaseGoods as $purchaseGoodsVal) {
                if ($purchaseGoodsVal['goodsId'] == $returnGoodsVal['goodsId'] && $purchaseGoodsVal['skuId'] == $returnGoodsVal['skuId']) {
                    $returnGoodsVal['purchaseGoodsId'] = $purchaseGoodsVal['id'];
                    $returnGoodsVal['returnOKGoodsNum'] = $purchaseGoodsVal['returnOKGoodsNum'];
                    $returnGoodsVal['warehouseOkNum'] = $purchaseGoodsVal['warehouseOkNum'];
                    $resetPurGoods[$returnGoodsVal['id']] = $returnGoodsVal;
                }
            }
        }
        unset($returnGoodsVal);
        $exWarehouseGoods = array();//出库商品备参
        $trans = new Model();
        $trans->startTrans();
        $goodsModule = new GoodsModule();
        foreach ($resetPurGoods as $item) {
            $purGoodsDetail = $item;
            $goodsName = $purGoodsDetail['goodsName'];
            $returnGoodsNum = (float)$item['returnGoodsNum'];
            if ($returnGoodsNum <= 0) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}请输入有效的退货数量");
            }
            $purchaseGoodsReturnNum = (float)bc_math($purGoodsDetail['returnOKGoodsNum'], $returnGoodsNum, 'bcadd', 3);//已退货数量+本次退货数量
            if ($purchaseGoodsReturnNum > (float)$purGoodsDetail['warehouseOkNum']) {
                $trans->rollback();
                $maxReturnNum = (float)bc_math($purGoodsDetail['warehouseOkNum'], $purGoodsDetail['returnOKGoodsNum'], 'bcsub', 3);
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}本次最多允许退货数量：{$maxReturnNum}");
            }
            $purchaseGoodsParams = array(
                'id' => $item['purchaseGoodsId'],
                'returnGoodsStatus' => 0,
                'returnOKGoodsNum' => (float)bc_math($purGoodsDetail['returnOKGoodsNum'], $returnGoodsNum, 'bcadd', 3),
            );
            if ($purchaseGoodsParams['returnOKGoodsNum'] > 0) {
                $purchaseGoodsParams['returnGoodsStatus'] = 1;
            }
            if ($purchaseGoodsParams['returnOKGoodsNum'] >= (float)$purGoodsDetail['warehouseOkNum']) {
                $purchaseGoodsParams['returnGoodsStatus'] = 2;
            }
            $savePurchaseGoodsRes = $module->savePurchaseBillGoods($purchaseGoodsParams, $trans);
            if (empty($savePurchaseGoodsRes)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "商品{$goodsName}采购信息更新失败");
            }
            $decGoodsStockRes = $goodsModule->deductionGoodsStock($purGoodsDetail['goodsId'], $purGoodsDetail['skuId'], $returnGoodsNum, 1, 1, $trans);
            if ($decGoodsStockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，商品{$goodsName}库房库存不足", "商品{$goodsName}库房库存更新失败");
            }
            $exWarehouseGoods[] = array(//出库单商品信息
                'goods_id' => $purGoodsDetail['goodsId'],
                'sku_id' => $purGoodsDetail['skuId'],
                'nums' => $returnGoodsNum,
                'unit_price' => $item['returnGoodsPrice'],
                'actual_delivery_quantity' => $returnGoodsNum,
                'goodsRemark' => (string)$item['returnGoodsRemark'],
                'remark' => $purGoodsDetail['describe'],
                'goods_unit' => $purGoodsDetail['unitName'],
                'goods_name' => $goodsName,
                'goods_specs_string' => $purGoodsDetail['skuSpecStr'],
            );
        }
        $returnBillParams = array(
            'returnId' => $returnId,
            'billStatus' => 1,
        );
        $returnId = $module->savePurchaseReturnBill($returnBillParams, $trans);
        if (empty($returnId)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败，采购退货单创建失败");
        }
        $exWarehouseOrderModule = new ExWarehouseOrderModule();
        $returnBillDetail = $module->getPurchaseReturnBillDetailById($returnId);
        $exWarehouseBillParams = array(
            'pagetype' => 4,
            'shopId' => $shopId,
            'user_id' => $loginInfo['user_id'],
            'user_name' => $loginInfo['user_username'],
            'remark' => '',
            'relation_order_id' => $returnId,
            'relation_order_number' => $returnBillDetail['billNo'],
            'goods_data' => $exWarehouseGoods,
        );
        $exRes = $exWarehouseOrderModule->addExWarehouseOrder($exWarehouseBillParams, $trans);//创建出库单
        if ($exRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "出库单创建失败");
        }
        $autoUpdatePurchaseBillStatusRes = $module->autoUpdatePurchaseBillStatus($purchaseId, $trans);
        if (!$autoUpdatePurchaseBillStatusRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "采购单退单状态更新失败");
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_purchase_return_bill',
            'dataId' => $returnId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'billStatus',
            'fieldValue' => 1,
            'remark' => '采购退货单已审核',
        ];
        $logRes = (new TableActionLogModule())->addTableActionLog($logParams, $trans);
        if ($logRes['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '日志记录失败');
        }
        $trans->commit();
        return returnData(true);
    }
}