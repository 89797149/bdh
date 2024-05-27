<?php
/**
 * 入库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-23
 * Time: 19:09
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Models\GoodsModel;
use App\Models\SkuGoodsSystemModel;
use App\Models\WarehousingBillGoodsModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use App\Modules\PurchaseBill\PurchaseBillModule;
use App\Modules\WarehousingBill\WarehousingBillModule;
use Think\Model;

class WarehousingBillModel extends BaseModel
{
    /**
     * 入库单-添加
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int billType 单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
     * -string billRemark 单据备注
     * -array goodsData 商品信息
     * --int goodsId 商品id
     * --int skuId 规格id
     * --float warehousNumTotal 应入库数量
     * --float warehousPrice 入库单价
     * --string goodsRemark 商品备注
     * @return array
     * */
    public function addWarehousingBill(array $paramsInput)
    {
        $module = new WarehousingBillModule();
        $result = $module->createWarehousingBill($paramsInput);
        return $result;
    }


    /**
     * 入库单-单据列表
     * @param array $paramInput
     * -int shopId 门店id
     * -int warehousingStatus 入库状态(0:未入库 1:已入库)
     * -int billType 单据类型(1:采购入库 2:其他入库 3:调货入库 4:订单退货 5:单位转换 6:期初入库 7:报溢入库)
     * -int dateType 日期类型(1:制单日期 2:入库日期)
     * -date dateStart 日期区间-开始日期
     * -date dateEnd 日期区间-结束日期
     * -string billNo 单号
     * -string relationBillNo 关联单号
     * -string creatorName 制单人
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getWarehousingBillList(array $paramInput)
    {
        $module = new WarehousingBillModule();
        $result = $module->getWarehousingBillList($paramInput);
        return $result;
    }

    /**
     * 入库单-商品列表(入库查询)
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
        $module = new WarehousingBillModule();
        $result = $module->getWarehousingBillGoodsList($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportWarehousingBillGoodsList($result);
        }
        return $result;
    }

    /**
     * 入库单-商品列表(入库查询)-导出
     * @param array $result
     * */
    public function exportWarehousingBillGoodsList(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/warehousing_bill_goods.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 2;
        $billAmountSum = 0;//应入库金额总计
        $billOkAmountSum = 0;//已入库金额总计
        foreach ($result as $detail) {
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['goodsName']);//名称
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['skuSpecStr']);//规格
            $objPHPExcel->getActiveSheet()->mergeCells("C{$keyTag}:D{$keyTag}");
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['shopCatId1Name'] . '/' . $detail['shopCatId2Name']);//门店分类
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['describe']);//商品描述
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['unitName']);//单位
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['billTypeName']);//入库类型
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detail['billNo']);//入库单号
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detail['warehousingTime']);//入库时间
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detail['warehousNumTotal']);//应入库数量
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $detail['warehouseOkNum']);//已入库数量
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $detail['warehousPrice']);//入库单价
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $detail['warehousePriceTotal']);//应入库金额
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $detail['warehouseOkPriceTotal']);//已入库金额
            $objPHPExcel->getActiveSheet()->mergeCells("O{$keyTag}:Q{$keyTag}");
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $detail['goodsRemark']);//商品备注
            $billAmountSum += (float)$detail['warehousePriceTotal'];
            $billOkAmountSum += (float)$detail['warehouseOkPriceTotal'];
        }
        $bottomRow = count($result) + 3;
        $objPHPExcel->getActiveSheet()->setCellValue("L{$bottomRow}", "总计：");
        $objPHPExcel->getActiveSheet()->setCellValue("M{$bottomRow}", $billAmountSum);
        $objPHPExcel->getActiveSheet()->setCellValue("N{$bottomRow}", $billOkAmountSum);
        $savefileName = '入库单查询导出_' . date('YmdHis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 入库单-详情/导出
     * @param int $warehousingId 入库单id
     * @param string $keywords 商品关键字
     * @param int $export 是否导出(0:否 1:是)
     * @return array
     * */
    public function getWarehousingBillDetail(int $warehousingId, string $keywords, int $export)
    {
        $module = new WarehousingBillModule();
        $billDetail = $module->getWarehousingBillDetailById($warehousingId);
        if (empty($billDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据异常');
        }
        $goodsData = $module->getWarehousingBillGoodsById($warehousingId, $keywords);
        $billDetail['goods_data'] = $goodsData;
        if ($export == 1) {
            $this->exportWarehousingBillDetail($billDetail);
        }
        $billDetail['log_data'] = $module->getWarehousingBillLogById($warehousingId);
        return $billDetail;
    }

    /**
     * 入库单-详情-导出
     * @param array $result
     * */
    public function exportWarehousingBillDetail(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/warehousing_bill_detail.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 2, $result['billNo']);//单号
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 3, $result['billTypeName']);//单据类型
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 4, $result['warehousingBillAmount']);//应入库金额

        $objPHPExcel->getActiveSheet()->setCellValue('E' . 2, $result['warehousingTime']);//入库时间
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 3, $result['relationBillNo']);//关联单号
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 4, $result['warehousingBillOkAmount']);//已入库金额

        $objPHPExcel->getActiveSheet()->setCellValue('G' . 2, $result['creatorName']);//制单人
        $objPHPExcel->getActiveSheet()->setCellValue('G' . 3, $result['billRemark']);//单据备注

        $keyTag = 6;
        $goodsData = $result['goods_data'];
        foreach ($goodsData as $detail) {
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detail['goodsName']);//名称
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detail['skuSpecStr']);//规格
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detail['describe']);//描述
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detail['unitName']);//单位
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detail['warehousPrice']);//入库单价
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detail['warehousNumTotal']);//应入库数量
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detail['warehouseOkNum']);//已入库数量
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detail['warehouseOkNum']);//应入库金额
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detail['warehousePriceTotal']);//应入库金额
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detail['warehouseOkPriceTotal']);//已入库金额
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detail['goodsRemark']);//商品备注
        }
        $savefileName = '入库单详情导出_' . $result['billNo'];
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
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
     * @return array
     * */
    public function inputWarehousingGoods(array $paramsInput)
    {
        $module = new WarehousingBillModule();
        $result = $module->inputWarehousingGoods($paramsInput);
        return $result;
    }

    /**
     * 入库单-审核入库
     * @param array $loginInfo 登陆者信息
     * @param int $warehousingId 入库单id
     * @return array
     * */
    public function actionWarehouse(array $loginInfo, int $warehousingId)
    {
        $module = new WarehousingBillModule();
        $billDetail = $module->getWarehousingBillDetailById($warehousingId);
        if (empty($billDetail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息异常');
        }
        if ($billDetail['warehousingStatus'] > 0) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已经审核入库的单据请勿重复操作');
        }
        $goodsData = $module->getWarehousingBillGoodsById($warehousingId);
        $saveGoodsData = array();
        $datetime = date('Y-m-d H:i:s');
        $detailRemark = '';
        $trans = new Model();
        $trans->startTrans();
        $goodsModle = new GoodsModel();
        $skuSystemModel = new SkuGoodsSystemModel();
        $goodsModule = new GoodsModule();
        foreach ($goodsData as $goodsItem) {
            $goodsName = $goodsItem['goodsName'];
            if ($goodsItem['goodsInputStatus'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "商品{$goodsName}未录入，请先录入");
            }
            $saveGoodsDetail = array(
                'id' => $goodsItem['id'],
                'warehouseStatus' => 1,
                'warehousingTime' => $datetime,
            );
            $saveGoodsData[] = $saveGoodsDetail;
            $detailRemark .= "商品{$goodsName}实际入库数量{$goodsItem['warehouseOkNum']} ";
            if ($billDetail['billType'] == 1) {//如果是采购入库类型,需要更新采购商品的实际入库数量
                $purchaseBillModule = new PurchaseBillModule();
                $purchaseGoodsList = $purchaseBillModule->getPurchaseGoodsList($billDetail['relationBillId']);
                foreach ($purchaseGoodsList as $purchaseGoodsDetail) {
                    if ($goodsItem['goodsId'] == $purchaseGoodsDetail['goodsId'] && $goodsItem['skuId'] == $purchaseGoodsDetail['skuId']) {
                        $purchaseGoodsParams = array(
                            'warehouseOkNum' => $goodsItem['warehouseOkNum'],
                            'warehousePrice' => $goodsItem['warehousPrice'],
                            'id' => $purchaseGoodsDetail['id'],
                        );
                        $savePurchaseGoodsRes = $purchaseBillModule->savePurchaseBillGoods($purchaseGoodsParams, $trans);
                        if (!$savePurchaseGoodsRes) {
                            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "入库失败", "采购单商品入库信息更新失败");
                        }
                    }
                }
            }
            //后加-审核后更新商品的库房数量和进货价
            $skuId = $goodsItem['skuId'];
            $goodsId = $goodsItem['goodsId'];
            if (empty($skuId)) {
                $goodsModle->where(array('goodsId' => $goodsId))->save(array('goodsUnit' => $goodsItem['warehousPrice']));
            } else {
                $skuSystemModel->where(array('skuId' => $skuId))->save(array('purchase_price' => $goodsItem['warehousPrice']));
            }
            $stockRes = $goodsModule->returnGoodsStock($goodsId, $skuId, $goodsItem['warehouseOkNum'], 1, 1, $trans);
            if ($stockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '创建失败', '商品库存更新失败');
            }
        }
        $model = new WarehousingBillGoodsModel();
        $saveGoodsRes = $model->saveAll($saveGoodsData, 'wst_warehousing_bill_goods', 'id');
        if (!$saveGoodsRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "入库失败", "商品信息更新失败");
        }
        $billData = array(
            'warehousingId' => $warehousingId,
            'warehousingStatus' => 1,
            'warehousingTime' => $datetime,
        );
        $saveBillRes = $module->saveWarehousingBill($billData, $trans);
        if (!$saveBillRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "入库失败", "单据信息更新失败");
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
            'fieldValue' => 1,
            'remark' => "入库完成$detailRemark",
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
     * 入库单-打印-记录打印次数
     * @param array $loginInfo 登陆者信息
     * @param int $warehousingId 入库单id
     * @return array
     * */
    public function incWarehousingBillPrintNum(array $loginInfo, int $warehousingId)
    {
        $module = new WarehousingBillModule();
        $detail = $module->getWarehousingBillDetailById($warehousingId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息异常');
        }
        if ($detail['shopId'] != $loginInfo['shopId']) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '单据信息和门店不匹配');
        }
        $trans = new Model();
        $trans->startTrans();
        $incRes = $module->incWarehousingBillPrintNum($warehousingId, $trans);
        if (!$incRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '打印记录失败');
        }
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_warehousing_bill',
            'dataId' => $warehousingId,
            'actionUserId' => $loginInfo['user_id'],
            'actionUserName' => $loginInfo['user_username'],
            'fieldName' => 'warehousingStatus',
            'fieldValue' => $detail['warehousingStatus'],
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
}