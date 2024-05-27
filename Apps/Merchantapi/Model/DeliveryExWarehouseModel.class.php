<?php
/**
 * 发货出库
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-09-06
 * Time: 15:27
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Enum\Orders\OrderEnum;
use App\Modules\ExWarehouse\DeliveryExWarehouseModule;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Log\LogOrderModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\PSD\DriverModule;
use App\Modules\PSD\LineModule;
use App\Modules\Settlement\CustomerSettlementBillModule;
use App\Modules\Shops\ShopsModule;
use App\Modules\Sorting\SortingModule;
use App\Modules\Users\UsersModule;
use App\Modules\WarehousingBill\WarehousingBillModule;
use Think\Model;

class DeliveryExWarehouseModel extends BaseModel
{
    /**
     * 发货出库-发货出库列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int sortingOrderStatus 分拣状态(0:未分拣 1:部分分拣 2:已分拣) 不传默认全部
     * -int deliveryStatus 发货状态(0:未发货 1:已发货) 不传默认全部
     * -int lineId 线路id,不传默认全部
     * -int requireDate 要求送达日期
     * -int paymentKeywords 关键字(客户名称/联系人/电话)
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 是否导出(0:不导出 1:导出)
     * @return array
     * */
    public function getDeliveryExWarehouseList(array $paramsInput)
    {
        $module = new DeliveryExWarehouseModule();
        $result = $module->getDeliveryExWarehouseList($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportDeliveryExWarehouseList($paramsInput, $result);
        }
        return returnData($result);
    }

    /**
     * 发货出库-发货出库列表-导出
     * @param array $paramsInput 筛选条件
     * @param array $result 发货出库列表列表
     * */
    public function exportDeliveryExWarehouseList(array $paramsInput, array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/deliveryExWarehouse.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 2;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 1, $paramsInput['requireDate'] . '送货单导出');
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        foreach ($result as $resultDetail) {
            foreach ($resultDetail['goods_list'] as $goodsDetail) {
                $keyTag++;
                $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:L{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $resultDetail['payment_username']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $goodsDetail['shopCatId1Name'] . '/' . $goodsDetail['shopCatId2Name']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $goodsDetail['goodsCode']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $goodsDetail['goodsName']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $goodsDetail['skuSpecStr']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $goodsDetail['goodsSpec']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $goodsDetail['unitName']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $goodsDetail['buyNum']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $goodsDetail['goodsPrice']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $goodsDetail['unitPrice']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $goodsDetail['okSortingStatusNum']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $goodsDetail['deliveryGoodsPrice']);
            }
            $keyTag++;
            $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:L{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, '合计');
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $resultDetail['realSortWeightTotal']);
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $resultDetail['deliveryGoodsPriceTotal']);
            $keyTag++;
        }
        $savefileName = '送货单导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 发货出库-发货出库详情
     * @param int $shopId 门店id
     * @param int $paymentUserid 客户id
     * @param date $requireDate 要求送达日期
     * @param int $orderId 订单id
     * @return array
     * */
    public function getDeliveryExWarehouseDetail(int $shopId, int $paymentUserid, $requireDate, int $orderId)
    {
        $module = new DeliveryExWarehouseModule();
        $result = $module->getDeliveryExWarehouseDetailById($shopId, $paymentUserid, $requireDate, $orderId);
        return returnData($result);
    }

    /**
     * 发货出库-订单发货出库列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int sortingOrderStatus 分拣状态(0:未分拣 1:部分分拣 2:已分拣) 不传默认全部
     * -int deliveryStatus 发货状态(0:未发货 1:已发货) 不传默认全部
     * -int lineId 线路id,不传默认全部
     * -int requireDate 要求送达日期
     * -int paymentKeywords 关键字(客户名称/订单号/联系人/电话)
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 是否导出(0:不导出 1:导出)
     * @return array
     * */
    public function getDeliveryExWarehouseOrderList(array $paramsInput)
    {
        $module = new DeliveryExWarehouseModule();
        $result = $module->getDeliveryExWarehouseOrderList($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportDeliveryExWarehouseOrder($paramsInput, $result);
        }
        return returnData($result);
    }


    /**
     * 发货出库-订单发货出库列表-导出
     * @param array $paramsInput 筛选条件
     * @param array $result 发货出库列表列表
     * */
    public function exportDeliveryExWarehouseOrder(array $paramsInput, array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/deliveryExWarehouseOrder.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 2;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 1, $paramsInput['requireDate'] . '送货单导出');
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        $deliveryGoodsPriceTotal = 0;//分拣金额总计
        $deliveryGoodsNumTotal = 0;//分拣数量总计
        foreach ($result as $resultDetail) {
            foreach ($resultDetail['goods_list'] as $goodsDetail) {
                $keyTag++;
                $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:O{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
                $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $resultDetail['payment_username']);
                $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $resultDetail['orderNo']);
                $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $goodsDetail['shopCatId1Name'] . '/' . $goodsDetail['shopCatId2Name']);
                $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $goodsDetail['goodsSpec']);
                $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $goodsDetail['goodsCode']);
                $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $goodsDetail['goodsName']);
                $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $goodsDetail['remarks']);
                $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $goodsDetail['skuSpecStr']);
                $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $goodsDetail['unitName']);
                $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $goodsDetail['buyNum']);
                $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $goodsDetail['goodsPrice']);
                $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $goodsDetail['unitPrice']);
                $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $goodsDetail['unitName']);
                $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $goodsDetail['realSortWeight']);
                $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $goodsDetail['deliveryGoodsPrice']);
                $deliveryGoodsNumTotal += $goodsDetail['realSortWeight'];
                $deliveryGoodsPriceTotal += $goodsDetail['deliveryGoodsPrice'];
            }
        }
        $keyTag++;
        $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:O{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
        $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, '合计：');
        $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $deliveryGoodsNumTotal);
        $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $deliveryGoodsPriceTotal);
        $savefileName = '送货单导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 发货出库-执行发货出库
     * @param array $loginInfo 登陆者
     * @param array $orderIdArr 关联订单id
     * @return array
     * */
    public function actionDeliveryExWarehouse(array $loginInfo, array $orderIdArr)
    {
        $shopId = $loginInfo['shopId'];
        $module = new DeliveryExWarehouseModule();
        $orderModule = new OrdersModule();
        $orderList = $orderModule->getOrderListById($orderIdArr, 'orderId,orderNo,shopId,orderStatus,isPay,userId,requireTime');
        if (empty($orderList)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择数据');
        }
        $orderShopId = implode(',', array_unique(array_column($orderList, 'shopId')));
        if ($orderShopId != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        foreach ($orderList as $orderDetail) {
            if ($orderDetail['isPay'] != 1) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据支付状态异常');
            }
            if (!in_array($orderDetail['orderStatus'], array(2, 16,))) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '当前状态不符合发货出库');
            }
            $paymentUserid = $orderDetail['userId'];
            $requireDate = date('Y-m-d', strtotime($orderDetail['requireTime']));
            $orderId = $orderDetail['orderId'];
            $deliveryExWarehouseDetail = $module->getDeliveryExWarehouseDetailById($shopId, $paymentUserid, $requireDate, $orderId);
            if (empty($deliveryExWarehouseDetail)) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请先分拣');
            }
            if ($deliveryExWarehouseDetail['sortingOrderStatus'] != 2) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请分拣完成后再操作发货出库');
            }
        }
        $trans = new Model();
        $trans->startTrans();
        $logTableModule = new LogOrderModule();
        $exWarehouseOrderModule = new ExWarehouseOrderModule();
        $sortingModule = new SortingModule();
        $customerSettlementModule = new CustomerSettlementBillModule();
        foreach ($orderList as $orderDetail) {
            $orderId = $orderDetail['orderId'];
            $orderGoods = $orderModule->getOrderGoodsList($orderId, 'og.*', 2);
            $goodsData = array();
            foreach ($orderGoods as $orderGoodsDetail) {
                $goodsDataDetail = array(
                    'goods_id' => $orderGoodsDetail['goodsId'],
                    'sku_id' => $orderGoodsDetail['skuId'],
                    'nums' => $orderGoodsDetail['goodsNums'],
                );
                $sortingOrderGoods = $sortingModule->getSortingOrderGoodsDetail($orderId, $orderGoodsDetail['goodsId'], $orderGoodsDetail['skuId']);
                $sortingGoodsDetail = $sortingModule->getSortingGoodsWeight($sortingOrderGoods['id'], $orderGoodsDetail['goodsId'], $orderGoodsDetail['skuId']);
                $goodsDataDetail['actual_delivery_quantity'] = $sortingGoodsDetail['sorting_ok_weight'];
                $goodsData[] = $goodsDataDetail;
            }
            $saveOrderParams = array(
                'orderId' => $orderId,
                'orderStatus' => 17,
            );
            $saveOrderRes = $orderModule->saveOrdersDetail($saveOrderParams, $trans);
            if (empty($saveOrderRes)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单信息更新失败');
            }
            $log_params = array(
                'orderId' => $orderId,
                'logContent' => '执行发货出库',
                'logUserId' => $loginInfo['user_id'],
                'logUserName' => $loginInfo['user_username'],
                'orderStatus' => 16,
                'payStatus' => 1,
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            );
            $log_res = $logTableModule->addLogOrders($log_params, $trans);
            if (!$log_res) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "订单日志记录失败");
            }
            $billData = array(
                'pagetype' => 1,
                'shopId' => $shopId,
                'user_id' => $loginInfo['user_id'],
                'user_name' => $loginInfo['user_username'],
                'remark' => '',
                'relation_order_number' => $orderDetail['orderNo'],
                'relation_order_id' => $orderDetail['orderId'],
                'goods_data' => $goodsData,
            );
            $addBillRes = $exWarehouseOrderModule->addExWarehouseOrder($billData, $trans);
            if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "出库单创建失败");
            }
            //创建客户结算单
            $settlementData = array(
                'loginInfo' => $loginInfo,
                'relation_order_id' => $orderDetail['orderId'],
                'billFrom' => 1,
            );
            $addSettlementRes = $customerSettlementModule->createCustomerSerrlementBill($settlementData, $trans);
            if ($addSettlementRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "客户结算单创建失败");
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 发货出库-打印
     * @param array $loginInfo 登陆者
     * @param array $orderIdArr 关联订单id
     * @return array
     * */
    public function printDeliveryExWarehouse(array $loginInfo, array $orderIdArr)
    {
        $shopId = $loginInfo['shopId'];
        $orderModule = new OrdersModule();
        $orderList = $orderModule->getOrderListById($orderIdArr, 'orderId,orderNo,shopId,orderStatus,isPay,userId,requireTime');
        if (empty($orderList)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '请选择数据');
        }
        $orderShopId = implode(',', array_unique(array_column($orderList, 'shopId')));
        if ($orderShopId != $shopId) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '数据异常');
        }
        $trans = new Model();
        $trans->startTrans();
        $logOrderModule = new LogOrderModule();
        foreach ($orderList as $orderDetail) {
            $incRes = $orderModule->incOrdersPrintNum($orderDetail['orderId'], $trans);
            if (!$incRes) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败');
            }
            $logParams = [
                'orderId' => $orderDetail['orderId'],
                'logContent' => '执行打印操作',
                'logUserId' => $loginInfo['user_id'],
                'logUserName' => $loginInfo['user_username'],
                'orderStatus' => $orderDetail['orderStatus'],
                'payStatus' => $orderDetail['isPay'],
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            ];
            $logRes = $logOrderModule->addLogOrders($logParams, $trans);
            if (!$logRes) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单日志记录失败');
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 发货出库-一键发货出库
     * @param array $paramsInput
     * -array loginInfo 登陆者信息
     * -date requireDate 要求送达日期
     * -string paymentKeywords 关键字(客户名称/订单号/联系人/电话)
     * -int lineId 线路id
     * @return array
     * */
    public function oneKeyActionDeliveryExWarehouse(array $paramsInput)
    {
        $module = new DeliveryExWarehouseModule();
        $loginInfo = $paramsInput['loginInfo'];
        $shopId = $loginInfo['shopId'];
        $paramsInput['shopId'] = $shopId;
        $paramsInput['usePage'] = 0;
        $paramsInput['deliveryStatus'] = 0;
        $paramsInput['sortingStatus'] = 2;
        $result = $module->getDeliveryExWarehouseOrderList($paramsInput);
        if (empty($result)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无已分拣未发货的数据');
        }
        $trans = new Model();
        $trans->startTrans();
        $logTableModule = new LogOrderModule();
        $orderModule = new OrdersModule();
        $exWarehouseOrderModule = new ExWarehouseOrderModule();
        $sortingModule = new SortingModule();
        foreach ($result as $orderDetail) {
            $orderId = $orderDetail['orderId'];
            $orderGoods = $orderModule->getOrderGoodsList($orderId, 'og.*', 2);
            $goodsData = array();
            foreach ($orderGoods as $orderGoodsDetail) {
                $goodsDataDetail = array(
                    'goods_id' => $orderGoodsDetail['goodsId'],
                    'sku_id' => $orderGoodsDetail['skuId'],
                    'nums' => $orderGoodsDetail['goodsNums'],
                );
                $sortingOrderGoods = $sortingModule->getSortingOrderGoodsDetail($orderId, $orderGoodsDetail['goodsId'], $orderGoodsDetail['skuId']);
                $sortingGoodsDetail = $sortingModule->getSortingGoodsWeight($sortingOrderGoods['id'], $orderGoodsDetail['goodsId'], $orderGoodsDetail['skuId']);
                $goodsDataDetail['actual_delivery_quantity'] = $sortingGoodsDetail['sorting_ok_weight'];
                $goodsData[] = $goodsDataDetail;
            }
            $saveOrderParams = array(
                'orderId' => $orderId,
                'orderStatus' => 17,
            );
            $saveOrderRes = $orderModule->saveOrdersDetail($saveOrderParams, $trans);
            if (empty($saveOrderRes)) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', '订单信息更新失败');
            }
            $log_params = array(
                'orderId' => $orderId,
                'logContent' => '执行发货出库',
                'logUserId' => $loginInfo['user_id'],
                'logUserName' => $loginInfo['user_username'],
                'orderStatus' => 16,
                'payStatus' => 1,
                'logType' => 1,
                'logTime' => date('Y-m-d H:i:s'),
            );
            $log_res = $logTableModule->addLogOrders($log_params, $trans);
            if (!$log_res) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "订单日志记录失败");
            }
            $billData = array(
                'pagetype' => 1,
                'shopId' => $shopId,
                'user_id' => $loginInfo['user_id'],
                'user_name' => $loginInfo['user_username'],
                'remark' => '',
                'relation_order_number' => $orderDetail['orderNo'],
                'relation_order_id' => $orderDetail['orderId'],
                'goods_data' => $goodsData,
            );
            $addBillRes = $exWarehouseOrderModule->addExWarehouseOrder($billData, $trans);
            if ($addBillRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '操作失败', "出库单创建失败");
            }
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 发货出库-获取打印数据
     * @param array $orderIdArr
     * @return array
     * */
    public function getPrintDeliveryExWarehouseData(array $orderIdArr)
    {
        $orderModule = new OrdersModule();
        $orderField = 'orderId,orderNo,orderRemarks,requireTime,shopId,userId,userName as consigneeName,userPhone as consigneePhone,userAddress,driverId,lineId,payFrom';
        $orderList = $orderModule->getOrderListById($orderIdArr, $orderField);
        if (empty($orderList)) {
            return array();
        }
        $returnData = array();//后更改,多笔订单合并到一笔订单商品
        $allGoodsList = array();
        $usersModule = new UsersModule();
        $shopsModule = new ShopsModule();
        $sortingModule = new SortingModule();
        $driverModule = new DriverModule();
        $lineModule = new LineModule();
        $ordersEnum = new OrderEnum();
        foreach ($orderList as $key => &$orderDetail) {
            $orderId = $orderDetail['orderId'];
            $customerDetial = $usersModule->getUsersDetailById($orderDetail['userId'], 'userName,userPhone', 2);
            $orderDetail['customerName'] = $customerDetial['userName'];
            $shopsDetail = $shopsModule->getShopsInfoById($orderDetail['shopId'], 'shopName,shopTel', 2);
            $orderDetail['shopName'] = $shopsDetail['shopName'];
            $orderDetail['shopTel'] = $shopsDetail['shopTel'];
            $driverDetail = $driverModule->getDriverDetailById($orderDetail['driverId'], 'driverName,driverPhone');
            $orderDetail['driverName'] = (string)$driverDetail['driverName'];
            $orderDetail['driverPhone'] = (string)$driverDetail['driverPhone'];
            $lineDetail = $lineModule->getLineDetailById($orderDetail['lineId']);
            $orderDetail['lineName'] = (string)$lineDetail['lineName'];
            $orderGoodsField = 'og.id,og.goodsId,og.skuId,og.goodsCode,og.goodsName,og.goodsSpec,og.goodsNums as buyGoodsNum,og.goodsPrice as buyUnitPrice,og.unitName,og.skuSpecStr,og.actionStatus,og.remarks';
            $orderGoods = $orderModule->getOrderGoodsList($orderId, $orderGoodsField, 2);
            $orderBuyGoodsPrice = 0;//下单金额
            $orderDeliveryGoodsPrice = 0;//发货金额
            foreach ($orderGoods as $gkey => $gval) {
                $goodsId = $gval['goodsId'];
                $skuId = $gval['skuId'];
                $gval['deliveryUnitPrice'] = $gval['buyUnitPrice'];
                $gval['buyGoodsPriceTotal'] = bc_math($gval['buyUnitPrice'], $gval['buyGoodsNum'], 'bcmul', 2);
                $sortingOrderGoodsDetail = $sortingModule->getSortingOrderGoodsDetail($orderId, $goodsId, $skuId);
                $sortingGoodsDetail = $sortingModule->getSortingGoodsWeight((int)$sortingOrderGoodsDetail['id'], $goodsId, $skuId);
                $gval['deliveryGoodsNum'] = (string)$sortingGoodsDetail['sorting_ok_weight'];
                $gval['deliveryGoodsPriceTotal'] = bc_math($gval['deliveryGoodsNum'], $gval['deliveryUnitPrice'], 'bcmul', 2);
                $gval['keyNum'] = $gkey + 1;
                $orderGoods[$gkey] = $gval;
                $orderBuyGoodsPrice += (float)$gval['buyGoodsPriceTotal'];
                $orderDeliveryGoodsPrice += (float)$gval['deliveryGoodsPriceTotal'];

                //后加
                $gval['keyNum'] = count($allGoodsList) + 1;
                $allGoodsList[] = $gval;
            }
            $orderDetail['orderBuyGoodsPrice'] = $orderBuyGoodsPrice;
            $orderDetail['orderDeliveryGoodsPrice'] = $orderDeliveryGoodsPrice;
            $orderDetail['orderDiffGoodsPrice'] = bc_math($orderDeliveryGoodsPrice, $orderBuyGoodsPrice, 'bcsub', 2);
            $orderDetail['goods_list'] = $orderGoods;
            $orderDetail['payFromName'] = $ordersEnum::getPayFromName()[$orderDetail['payFrom']];
            $orderDetail['inviteUserName'] = '';//业务员名称 PS:暂定分销人员
            $orderDetail['inviteUserPhone'] = '';//业务员手机号 PS:暂定分销人员
            $inviteUserDetail = $usersModule->getBusinessPersonnelDetail($customerDetial['userPhone']);
            if (!empty($inviteUserDetail)) {
                $orderDetail['inviteUserName'] = $inviteUserDetail['inviteUserName'];
                $orderDetail['inviteUserPhone'] = $inviteUserDetail['inviteUserPhone'];
            }
            $orderDetail['requireDate'] = date('Y-m-d', strtotime($orderDetail['requireTime']));
            if ($key == 0) {
                $returnData = $orderDetail;
            } else {
                if (!empty($returnData)) {
                    $returnData['orderBuyGoodsPrice'] += $orderBuyGoodsPrice;
                    $returnData['orderDeliveryGoodsPrice'] += $orderDeliveryGoodsPrice;
                    $returnData['orderDiffGoodsPrice'] = bc_math($returnData['orderDeliveryGoodsPrice'], $returnData['orderBuyGoodsPrice'], 'bcsub', 2);
                }
            }
        }
        unset($orderDetail);
        if ((float)$returnData['orderDiffGoodsPrice'] < 0) {
            $returnData['orderDiffGoodsPrice'] = "退" . abs($returnData['orderDiffGoodsPrice']);
        }
        if ((float)$returnData['orderDiffGoodsPrice'] > 0) {
            $returnData['orderDiffGoodsPrice'] = "补" . abs($returnData['orderDiffGoodsPrice']);
        }
        $returnData['goods_list'] = $allGoodsList;
//        return $orderList;
        return array($returnData);
    }

    /**
     * 发货出库-商品发货差异列表
     * @param array $paramsInput
     * -int shopId 门店id
     * -int lineId 线路id
     * -date requireDate 要求送达日期
     * -string goodsKeywords 商品关键字(商品名/编码)
     * -int sortDiffNum 分拣差异值(1:差异小于0 2:差异大于0 3:差异等于0) 不传默认全部
     * -int sortingGoodsStatus 商品分拣状态(0:未分拣 1:已分拣) 不传默认全部
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 导出(0:不导出 1:导出)
     * @return array
     * */
    public function getDiffDeliveryExWarehouseGoods(array $paramsInput)
    {
        $module = new DeliveryExWarehouseModule();
        if ($paramsInput['export'] == 1) {
            $paramsInput['usePage'] = 0;
        }
        $result = $module->getDiffDeliveryExWarehouseGoods($paramsInput);
        if ($paramsInput['export'] == 1) {
            $this->exportDiffDeliveryExWarehouseGoods($paramsInput, $result);
        }
        return $result;
    }

    /**
     * 发货出库-商品发货差异列表-导出
     * @param array $paramsInput 筛选条件
     * @param array $result 商品发货差异列表
     * */
    public function exportDiffDeliveryExWarehouseGoods(array $paramsInput, array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/deliveryExWarehouseDiff.xlsx';
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 2;
        $objPHPExcel->getActiveSheet()->setCellValue('A' . 1, $paramsInput['requireDate'] . '库存差异');
        $styleThinBlackBorderOutline = array(
            'borders' => array(
                'allborders' => array( //设置全部边框
                    'style' => \PHPExcel_Style_Border::BORDER_THIN //粗的是thick
                ),

            ),
        );
        foreach ($result as $detial) {
            $keyTag++;
            $objPHPExcel->getActiveSheet()->getStyle("A{$keyTag}:I{$keyTag}")->applyFromArray($styleThinBlackBorderOutline);
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $detial['goodsCode']);
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $detial['goodsName']);
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $detial['skuSpecStr']);
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $detial['goodsSpec']);
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $detial['sortingGoodsStatusName']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $detial['unitName']);
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $detial['goodsStock']);
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $detial['sortingNum']);
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $detial['sortingDiffNum']);
        }
        $savefileName = '库存差异导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 发货出库-发货差异商品报溢
     * @param string $requireDate 要求送达日期
     * @param array $idArr 订单商品唯一标识id
     * @param array $loginInfo 登陆者信息
     * @return array
     * */
    public function diffGoodsReportedOverflow($requireDate, array $idArr, array $loginInfo)
    {
        $module = new DeliveryExWarehouseModule();
        if (empty($idArr)) {//一键报溢
            $paramsInput = array(
                'shopId' => $loginInfo['shopId'],
                'requireDate' => $requireDate,
                'sortDiffNum' => 1,
                'sortingGoodsStatus' => 1,
                'usePage' => 0,
            );
        } else {//多选
            $paramsInput = array(
                'shopId' => $loginInfo['shopId'],
                'idArr' => $idArr,
                'requireDate' => $requireDate,
                'sortDiffNum' => 1,
                'sortingGoodsStatus' => 1,
                'usePage' => 0,
            );
        }
        $result = $module->getDiffDeliveryExWarehouseGoods($paramsInput);
        if (empty($result)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '暂无需要报损的数据');
        }
        $orderList = array();
        foreach ($result as $item) {
            $orderList[$item['orderId']]['orderId'] = $item['orderId'];
            $orderList[$item['orderId']]['orderNo'] = $item['orderNo'];
            $orderList[$item['orderId']]['goods_list'][] = $item;
        }
        $warehousingModule = new WarehousingBillModule();
        $trans = new Model();
        $trans->startTrans();
        $orderModule = new OrdersModule();
        foreach ($orderList as $item) {
            $warehousBill = array(
                'loginInfo' => $loginInfo,
                'billType' => 7,
                'relationBillId' => $item['orderId'],
                'relationBillNo' => $item['orderNo'],
                'billRemark' => '',
                'goodsData' => array(),
            );
            $goodsData = array();
            foreach ($item['goods_list'] as $goodsItem) {
                $goodsDetail = array(
                    'goodsId' => $goodsItem['goodsId'],
                    'skuId' => $goodsItem['skuId'],
                    'warehousNumTotal' => abs($goodsItem['sortingDiffNum']),
                    'warehousPrice' => $goodsItem['purchase_price'],
                    'goodsRemark' => $goodsItem['remarks'],
                );
                $goodsData[] = $goodsDetail;
                $orderGoodsParams = array(
                    'id' => $goodsItem['id'],
                    'reportedOverflow' => 1
                );
                $saveRes = $orderModule->saveOrderGoods($orderGoodsParams, $trans);
                if (empty($saveRes)) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "订单商品信息更新失败");
                }
            }
            $warehousBill['goodsData'] = $goodsData;
            $wareRes = $warehousingModule->createWarehousingBill($warehousBill, $trans);
            if ($wareRes['code'] != ExceptionCodeEnum::SUCCESS) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', "操作失败", "入库单创建失败");
            }
        }
        $trans->commit();
        return returnData(true);
    }
}