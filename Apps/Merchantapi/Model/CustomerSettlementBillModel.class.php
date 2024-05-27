<?php
/**
 * 客户结算单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-16
 * Time: 19:13
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Settlement\CustomerSettlementBillEnum;
use App\Enum\Settlement\SettlementBillEnum;
use App\Modules\Log\TableActionLogModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Settlement\CustomerSettlementBillModule;

class CustomerSettlementBillModel
{
    /**
     * 结算单-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int payType 支付方式(1:支付宝，2：微信 3:余额  4:货到付款 5:现金 6:转账 7:线下支付宝 8:线下微信)
     * -date createDateStart 单据创建日期区间-开始日期
     * -date createDateEnd 单据创建日期区间-结束日期
     * -string customerKeywords 客户信息(客户名称/联系人/手机号)
     * -string settlementNo 结算单号
     * -string relation_order_number 业务单号
     * -int page 页码
     * -int pageSize 页码
     * */
    public function getCustomerSettlementBillList(array $paramsInput)
    {
        $module = new CustomerSettlementBillModule();
        $result = $module->getCustomerSettlementBillList($paramsInput);
        return $result;
    }

    /**
     * 结算单-详情
     * @param int $settlementId 结算单id
     * @param int $shopId 门店id
     * @param int $export 导出(0:否 1:是)
     * @return array
     * */
    public function getCustomerSettlementBillDetail(int $settlementId, int $shopId, int $export)
    {
        $module = new CustomerSettlementBillModule();
        $detail = $module->getCustomerSettlementBillDetailById($settlementId);
        if (empty($detail)) {
            return array();
        }
        if ($detail['shopId'] != $shopId) {
            return array();
        }
        $relationBillData = $module->getRelationBillData($settlementId);
        $detail['relation_bill_list'] = $relationBillData;
        if ($export == 1) {
            $this->exportCustomerSettlementBillDetail($detail);
        }
        $logWhere = array(
            'tableName' => 'wst_customer_settlement_bill',
            'dataId' => $settlementId,
        );
        $logField = 'logId,actionUserName,remark,createTime';
        $detail['log_data'] = (new TableActionLogModule())->getLogListByParams($logWhere, $logField);
        return $detail;
    }

    /**
     * 结算单-详情-导出
     * @param array $result
     * */
    public function exportCustomerSettlementBillDetail(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/customer_settlement_bill_detail.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 2, $result['settlementNo']);
        $objPHPExcel->getActiveSheet()->setCellValue('D' . 2, $result['createTime']);
        $objPHPExcel->getActiveSheet()->setCellValue('F' . 2, $result['customerName']);
        $objPHPExcel->getActiveSheet()->setCellValue('J' . 2, $result['creatorName']);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 3, $result['payerName']);
        $objPHPExcel->getActiveSheet()->setCellValue('D' . 3, $result['billStatusName']);
        $objPHPExcel->getActiveSheet()->setCellValue('F' . 3, $result['payTypeName']);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . 3, $result['billAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('J' . 3, $result['receivableAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 4, $result['zeroAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('D' . 4, $result['billRemark']);

        $relationBillRow = $result['relation_bill_list'][0];//目前没有多条的场景,先做一条关联记录
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 7, $relationBillRow['keyNum']);
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 7, $relationBillRow['originalNo']);
        $objPHPExcel->getActiveSheet()->setCellValue('C' . 7, $relationBillRow['businessTypeName']);
        $objPHPExcel->getActiveSheet()->setCellValue('D' . 7, $relationBillRow['receivableAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 7, $relationBillRow['receivedAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('F' . 7, $relationBillRow['uncollectedAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('G' . 7, $relationBillRow['paidInAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('H' . 7, $relationBillRow['zeroAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('I' . 7, $relationBillRow['dueDate']);
        $objPHPExcel->getActiveSheet()->setCellValue('J' . 7, $relationBillRow['billRemark']);

        $savefileName = '客户结算单详情导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 客户结算-列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -date requireDateStart 要求送达日期区间-开始日期
     * -date requireDateEnd 要求送达日期区间-结束日期
     * -string customerKeywords 关键字搜索(客户名称/手机号/订单号)
     * -date customerSettlementDateStart 结算日期区间-开始日期
     * -date customerSettlementDateEnd 结算日期区间-结束日期
     * -date payTimeStart 支付日期区间-开始日期
     * -date payTimeEnd 支付日期区间-结束日期
     * -int customerSettlementStatus 客户结算状态(0:未结算 1:已结算) 不传默认全部
     * -int deliveryStatus 发货状态(0:未发货 1:已发货) 不传默认全部
     * -int returnStatus 退货状态(0:无退货 1:有退货) 不传默认全部
     * -int lineId 线路id
     * -int driverId 司机id
     * -int rankId 客户类型id
     * -array payFrom 支付方式(1:支付宝，2：微信,3:余额,4:货到付款)
     * -int export 导出(0:否 1:是)
     * -int page 页码
     * -int pageSize 每页条数
     * @return array
     * */
    public function getCustomerSettlementList(array $paramsInput)
    {
        $mod = new CustomerSettlementBillModule();
        if ($paramsInput['export'] == 1) {
            $paramsInput['usePage'] = 0;
        }
        $result = $mod->getCustomerSettlementList($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportCustomerSettlementList($result);
        }
        return $result;
    }

    /**
     * 客户结算-列表-导出
     * @param array $result
     * */
    public function exportCustomerSettlementList(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/customer_settlement_bill_list.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $keyTag = 2;
        foreach ($result['root'] as $detial) {
            $keyTag++;
            $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:P{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detial['orderNo']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detial['customerName']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detial['requireTime']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detial['lineName']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detial['driverName']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detial['customerSettlementStatus']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detial['payFromName']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detial['orderRemarks']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detial['diffAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detial['returnAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $detial['deliverMoney']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $detial['receivableAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $detial['receivedAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $detial['uncollectedAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $detial['paidInAmount']);
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $keyTag, $detial['zeroAmount']);
        }
        $savefileName = '客户结算列表导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 客户结算-详情
     * @param int $orderId 订单id
     * @param int $export 导出(0:不导出 1:导出)
     * @param int $shopId 门店id
     * @return array
     * */
    public function getCustomerSettlementDetail(int $orderId, int $export, int $shopId)
    {
        $module = new CustomerSettlementBillModule();
        $ordersModule = new OrdersModule();
        $ordersData = $ordersModule->getOrderInfoById($orderId);
        if ($ordersData['code'] != ExceptionCodeEnum::SUCCESS) {
            return array();
        }
        $ordersRow = $ordersData['data'];
        if ($ordersRow['shopId'] != $shopId) {
            return array();
        }
        $settlementId = (int)$ordersRow['customerSettlementId'];
        $settlementDetail = $module->getCustomerSettlementBillDetailById($settlementId);
        $returnData = array(
            'orderId' => $ordersRow['orderId'],
            'orderNo' => $ordersRow['orderNo'],//订单号
            'customerName' => (string)$ordersRow['payment_username'],//客户名称
            'createTime' => '',//创建时间
            'requireTime' => $ordersRow['requireTime'],//要求送达时间
            'customerSettlementStatus' => '未结算',//结算状态
            'billAmount' => $ordersRow['receivableAmount'],//单据金额
            'billRemark' => '',//单据单据备注
            'goods_list' => array(),//订单商品清单
            'log_data' => array(),//操作日志
        );
        if (!empty($settlementDetail)) {
            $returnData['createTime'] = $settlementDetail['createTime'];
            $returnData['customerSettlementStatus'] = CustomerSettlementBillEnum::getBillStatus()[$settlementDetail['billStatus']];
            $returnData['billAmount'] = $settlementDetail['billAmount'];
            $returnData['billRemark'] = $settlementDetail['billRemark'];
        }
        $orderGoodsList = $ordersModule->getOrderGoodsList($orderId, 'og.*', 2);
        $returnGoodsList = array();//商品信息
        $keyNum = 0;
        $deliveryGoodsNumTotal = 0;
        $deliveryGoodsPriceTotal = 0;
        $diffGoodsNumTotal = 0;
        $diffGoodsPriceTotal = 0;
        foreach ($orderGoodsList as $item) {
            $keyNum++;
            $returnGoodsDetail = array(
                'keyNum' => $keyNum,
                'goodsName' => $item['goodsName'],
                'goodsCode' => $item['goodsCode'],
                'skuSpecStr' => $item['skuSpecStr'],
                'goodsSpec' => $item['goodsSpec'],
                'buyUnitName' => $item['unitName'],//下单单位
                'deliveryUnitName' => $item['unitName'],//发货单位
                'buyNum' => $item['goodsNums'],//购买数量
                'buyUnitPrice' => $item['goodsPrice'],//购买单价
                'buyPriceTotal' => bc_math($item['goodsPrice'], $item['goodsNums'], 'bcmul', 2),//下单金额小计
                'deliveryNum' => $item['sortingNum'],//发货数量
                'deliveryUnitPrice' => $item['goodsPrice'],//发货单价
                'deliveryPriceTotal' => bc_math($item['goodsPrice'], $item['sortingNum'], 'bcmul', 2),//发货金额小计
                'diffNum' => bc_math($item['sortingNum'], $item['goodsNums'], 'bcsub', 3),//差异数量
            );
            $returnGoodsDetail['diffUnitPrice'] = bc_math($returnGoodsDetail['deliveryUnitPrice'], $returnGoodsDetail['buyUnitPrice'], 'bcsub', 3);//差异单价
            $returnGoodsDetail['diffPriceTotal'] = bc_math($returnGoodsDetail['deliveryPriceTotal'], $returnGoodsDetail['buyPriceTotal'], 'bcsub', 3);//差异金额
            $deliveryGoodsNumTotal += $returnGoodsDetail['deliveryNum'];
            $deliveryGoodsPriceTotal += $returnGoodsDetail['deliveryPriceTotal'];
            $diffGoodsNumTotal += $returnGoodsDetail['diffNum'];
            $diffGoodsPriceTotal += $returnGoodsDetail['diffPriceTotal'];
            $returnGoodsList[] = $returnGoodsDetail;
        }
        $returnData['goods_list'] = $returnGoodsList;
        $returnData['deliveryGoodsNumTotal'] = formatAmountNum($deliveryGoodsNumTotal, 3);//发货数量合计
        $returnData['deliveryGoodsPriceTotal'] = formatAmountNum($deliveryGoodsPriceTotal);//发货金额合计
        $returnData['diffGoodsNumTotal'] = formatAmountNum($diffGoodsNumTotal, 3);//差异数量合计
        $returnData['diffGoodsPriceTotal'] = formatAmountNum($diffGoodsPriceTotal);//差异金额合计
        if ($export == 1) {
            $this->exportCustomerSettlementDetail($returnData);
        }
        $logWhere = array(
            'tableName' => 'wst_customer_settlement_bill',
            'dataId' => $settlementId,
        );
        $logField = 'logId,actionUserName,remark,createTime';
        $returnData['log_data'] = (new TableActionLogModule())->getLogListByParams($logWhere, $logField);
        return $returnData;
    }

    /**
     * 客户结算-详情-导出
     * @param array $result
     * */
    public function exportCustomerSettlementDetail(array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/customer_settlement_detail.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $objPHPExcel->getActiveSheet()->setCellValue('B2', $result['orderNo']);
        $objPHPExcel->getActiveSheet()->setCellValue('B3', $result['requireTime']);
        $objPHPExcel->getActiveSheet()->setCellValue('D2', $result['customerName']);
        $objPHPExcel->getActiveSheet()->setCellValue('D3', $result['customerSettlementStatus']);
        $objPHPExcel->getActiveSheet()->setCellValue('L2', $result['createTime']);
        $objPHPExcel->getActiveSheet()->setCellValue('I3', $result['billAmount']);
        $objPHPExcel->getActiveSheet()->setCellValue('L3', $result['diffGoodsPriceTotal']);
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $keyTag = 5;
        foreach ($result['goods_list'] as $detial) {
            $keyTag++;
            $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:L{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detial['keyNum']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detial['goodsName']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detial['goodsSpec']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detial['buyUnitName']);
            $objPHPExcel->getActiveSheet()->mergeCells("E{$keyTag}:F{$keyTag}");
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detial['skuSpecStr']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detial['deliveryNum']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detial['deliveryUnitPrice']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detial['deliveryPriceTotal']);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $detial['diffNum']);
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $detial['diffUnitPrice']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $detial['diffPriceTotal']);
        }
        $savefileName = '客户结算详情导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 客户结算-结算记录
     * @param int $orderId 订单id
     * @param int $shopId 门店id
     * @return array
     * */
    public function getCustomerSettlementLog(int $orderId, int $shopId)
    {
        $ordersModule = new OrdersModule();
        $orderData = $ordersModule->getOrderInfoById($orderId);
        if ($orderData['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(true, ExceptionCodeEnum::FAIL, 'error', '订单信息有误');
        }
        $orderRow = $orderData['data'];
        if ($orderRow['shopId'] != $shopId) {
            return returnData(true, ExceptionCodeEnum::FAIL, 'error', '订单与门店不匹配');
        }
        if (empty($orderRow['customerSettlementId'])) {
            return returnData(true, ExceptionCodeEnum::FAIL, 'error', '暂无结算记录');
        }
        $customerSettlementModule = new CustomerSettlementBillModule();
        $orderSettlementList = $customerSettlementModule->getOrderSettlementList($orderId);
        $settlementList = array();
        foreach ($orderSettlementList as $key => $detail) {
            $settlementDetail = array(
                'keyNum' => $key + 1,
                'originalOrderNo' => $detail['relation_order_number'],
                'billTypeName' => CustomerSettlementBillEnum::getBillFrom()[$detail['billFrom']],
                'settlementNo' => $detail['settlementNo'],
                'creatorName' => $detail['creatorName'],
                'payerName' => $detail['payerName'],
                'payTypeName' => CustomerSettlementBillEnum::getPayType()[$detail['payType']],
                'receivableAmount' => $detail['receivableAmount'],
                'zeroAmount' => $detail['zeroAmount'],
                'customerSettlementDate' => $detail['createTime'],
                'billRemark' => $detail['billRemark'],
            );
            $settlementList[] = $settlementDetail;
        }
        $diffList = array();
        if (!empty((float)$orderRow['diffAmount'])) {
            $diffList = array(
                array(
                    'keyNum' => 1,
                    'originalOrderNo' => $orderRow['orderNo'],//原始单号
                    'underpaidAmount' => $orderRow['diffAmount'] > 0 ? $orderRow['diffAmount'] : '0',//少补金额
                    'multiAmount' => $orderRow['diffAmount'] < 0 ? $orderRow['diffAmount'] : '0',//多退金额
                    'actionTime' => (string)$orderRow['diffAmountTime'],//操作日期,
                    'diffRemark' => "订单少补系统收款记录,订单号：{$orderRow['orderNo']}",//多退少补备注,
                ),
            );
        }
        $returnData = array(
            'customerName' => $orderRow['payment_username'],
            'settlementSum' => array(
                'receivableAmountTotal' => formatAmountNum(array_sum(array_column($settlementList, 'receivableAmount'))),
                'zeroAmountTotal' => formatAmountNum(array_sum(array_column($settlementList, 'zeroAmount'))),
            ),
            'settlementList' => $settlementList,
            'diffList' => $diffList,
        );
        //模拟数据
//        $returnData = array(
//            'customerName' => 'YHJ',
//            'settlementSum' => array(
//                'receivableAmountTotal' => '8',//结算单实收金额合计
//                'zeroAmountTotal' => '0',//结算单抹零金额合计
//            ),
//            'settlementList' => array(
//                array(
//                    'keyNum' => 1,
//                    'originalOrderNo' => 'Y22893289323',//原始单号
//                    'billTypeName' => '销售订单',//业务类型
//                    'settlementNo' => 'SE2029309230920',//结算单号
//                    'creatorName' => '爱果厨',//制单人
//                    'payerName' => 'YHJ',//交款人
//                    'payTypeName' => '余额',//交款方式
//                    'receivableAmount' => '8',//实收金额
//                    'zeroAmount' => '0',//抹零金额
//                    'customerSettlementDate' => '2021-09-18 12:00:00',//结算日期
//                    'billRemark' => '在线支付自动结算',//结算单备注
//                ),
//            ),
//            'diffList' => array(
//                array(
//                    'keyNum' => 1,
//                    'originalOrderNo' => 'Y22893289323',//原始单号
//                    'underpaidAmount' => '0',//少补金额
//                    'multiAmount' => '0',//多退金额
//                    'actionTime' => '2021-09-18 12:00:00',//操作日期,
//                    'diffRemark' => '订单少补系统收款记录,订单号：Y22893289323',//多退少补备注,
//                ),
//            ),
//        );
        return returnData($returnData);
    }

    /**
     * 客户结算-结算-首次结算预览
     * @param int $orderId 订单id
     * @param int $shopId 门店id
     * @return array
     * */
    public function preDoCustomerSettlement(int $orderId, int $shopId)
    {
        $ordersModule = new OrdersModule();
        $orderData = $ordersModule->getOrderInfoById($orderId);
        if ($orderData['code'] != ExceptionCodeEnum::SUCCESS) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单异常');
        }
        $orderRow = $orderData['data'];
        if ($orderRow['shopId'] != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '订单与门店不匹配');
        }
        if (!empty($orderRow['customerSettlementId'])) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已结算订单请勿重复结算');
        }
        $settlementList = array();
        $settlementList[] = array(
            'keyNum' => 1,
            'dataId' => (string)$orderId,
            'originalOrderNo' => $orderRow['orderNo'],
            'customerName' => $orderRow['payment_username'],
            'billTypeName' => '销售订单',
            'billType' => '1',
            'receivableAmount' => $orderRow['receivableAmount'],
            'receivedAmount' => formatAmountNum(0),
            'uncollectedAmount' => $orderRow['uncollectedAmount'],
            'dueDate' => $orderRow['payFrom'] == 4 ? $orderRow['requireTime'] : $orderRow['pay_time'],
        );
        $orderComplainsList = $ordersModule->getComplainsListByorderId($orderId);
        $afterSettlementList = array();
        $afterKeyNum = 1;
        $orderDiffGoods = $ordersModule->getOrderGoodsDiffList($orderId, 3);//补差价信息
        foreach ($orderComplainsList as $item) {
            if ($item['returnAmountStatus'] == 1) {
                continue;
            }
            $afterKeyNum++;
            $proposalGoodsPrice = handleGoodsPayN($orderId, $item['goodsId'], $item['skuId'], $orderRow['userId']) * $item['returnNum'];//建议退款金额
            foreach ($orderDiffGoods as $diffRow) {
                if ($diffRow['goodsId'] == $item['goodsId'] && $diffRow['skuId'] == $item['skuId']) {
                    $proposalGoodsPrice -= $item['money'];
                }
            }
            $afterSettlementDetail = array(
                'keyNum' => $afterKeyNum,
                'dataId' => $item['complainId'],
                'originalOrderNo' => $orderRow['orderNo'],
                'customerName' => $orderRow['payment_username'],
                'billTypeName' => '订单退货',
                'billType' => '2',
                'receivableAmount' => formatAmountNum(-$proposalGoodsPrice),
                'receivedAmount' => formatAmountNum(0),
                'uncollectedAmount' => formatAmountNum(-$proposalGoodsPrice),
                'dueDate' => $item['createTime'],
            );
            $afterSettlementList[] = $afterSettlementDetail;
        }
        if (!empty($afterSettlementList)) {
            $settlementList = array_merge($settlementList, $afterSettlementList);
        }
        $returnData = array(
            'billAmount' => 0,
            'settlementSum' => array(
                'receivableAmountTotal' => formatAmountNum(array_sum(array_column($settlementList, 'receivableAmount'))),//应收金额合计
                'receivedAmountTotal' => formatAmountNum(array_sum(array_column($settlementList, 'receivedAmount'))),//已收金额合计
                'uncollectedAmountTotal' => formatAmountNum(array_sum(array_column($settlementList, 'uncollectedAmount'))),//未收金额合计
            ),
            'settlementList' => $settlementList,
        );
        $returnData['billAmount'] = $returnData['settlementSum']['uncollectedAmountTotal'];
        //数据模拟
//        $returnData = array(
//            'billAmount' => '6',//本次付款金额
//            'settlementSum' => array(
//                'receivableAmountTotal' => '8',//应收金额合计
//                'receivedAmountTotal' => '0',//已收金额合计
//                'uncollectedAmountTotal' => '6',//未收金额合计
//            ),
//            'settlementList' => array(
//                array(
//                    'keyNum' => 1,
//                    'dataId' => '1',
//                    'originalOrderNo' => 'YS202121323',
//                    'customerName' => 'YHJ',
//                    'billTypeName' => '销售订单',
//                    'billType' => '1',//单据类型(1:销售订单 2:退货单)
//                    'receivableAmount' => '8',//应收金额
//                    'receivedAmount' => '0',//已收金额
//                    'uncollectedAmount' => '8',//未收金额
//                    'dueDate' => '2021-09-18',//应收日期
//                ),
//                array(
//                    'keyNum' => 2,
//                    'dataId' => '10',//数据标识id
//                    'originalOrderNo' => 'RT202121323',//原始单号
//                    'customerName' => 'YHJ',//客户名称
//                    'billTypeName' => '订单退货',//单据类型名称
//                    'billType' => '2',//单据类型(1:销售订单 2:退货单)
//                    'receivableAmount' => '-2',//应收金额
//                    'receivedAmount' => '0',//已收金额
//                    'uncollectedAmount' => '-2',//未收金额
//                    'dueDate' => '2021-09-18',//应收日期
//                ),
//            ),
//        );

        return returnData($returnData);
    }

    /**
     * 客户结算-结算-确认结算
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -int payType 支付方式(1:支付宝，2：微信 3:余额  4:货到付款 5:现金 6:转账 7:线下支付宝 8:线下微信)
     * -string payerName 交款人
     * -array billPic 单据照片
     * -string billRemark 单据备注
     * -array settlementList 结算单信息
     * @return array
     * */
    public function doCustomerSettlement(array $paramsInput)
    {
        $module = new CustomerSettlementBillModule();
        $result = $module->doCustomerSettlement($paramsInput);
        return $result;
    }
}