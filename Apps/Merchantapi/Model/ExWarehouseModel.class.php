<?php
/**
 * 出库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-06
 * Time: 17:28
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use Think\Model;

class ExWarehouseModel extends BaseModel
{
    /**
     * 出库单-创建
     * @param array $billData 单据信息
     * -int shopId 门店id
     * -int user_id 制单人id
     * -string user_name 制单人姓名
     * -string remark 单据备注
     * -array goods_data 商品数据
     * --int goods_id 商品id
     * --int sku_id 商品skuId
     * --float nums 出库数量
     * @return array
     * */
    public function addExWarehouseOrder(array $billData)
    {
        $module = new ExWarehouseOrderModule();
        $billData['pagetype'] = 2;//出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
        $trans = new Model();
        $trans->startTrans();
        $result = $module->addExWarehouseOrder($billData, $trans);
        if ($result['code'] != ExceptionCodeEnum::SUCCESS) {
            $trans->rollback();
            return $result;
        }
        $goodsModule = new GoodsModule();
        $config = $GLOBALS['CONFIG'];
        if ($config['ex_warehouse_order_examine'] != 1 && $billData['pagetype'] == 2) {
            $goodsData = $billData['goods_data'];
            foreach ($goodsData as $goodsVal) {
                $goodsId = (int)$goodsVal['goods_id'];
                $skuId = (int)$goodsVal['sku_id'];
                $stock = $goodsVal['nums'];
                if (isset($goodsVal['actual_delivery_quantity'])) {
                    $stock = $goodsVal['actual_delivery_quantity'];
                }
                $stockRes = $goodsModule->returnGoodsStock($goodsId, $skuId, (float)$stock, 1, 1, $trans);
                if ($stockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                    $trans->rollback();
                    return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单创建失败", "出库单创建失败，商品库房库存更新失败");
                }
            }
        }
        $trans->commit();
        return $result;
    }

    /**
     * 出库单-更新
     * @param array $billData 单据信息
     * -int shopId 门店id
     * -int user_id 制单人id
     * -string user_name 制单人姓名
     * -int ex_order_id 出库单id
     * -string remark 单据备注
     * -array goods_data 商品数据
     * --int id 出库商品唯一标识id
     * --float actual_delivery_quantity 实际出库数量
     * @return array
     * */
    public function updateExWarehouseOrder(array $billData)
    {
        $module = new ExWarehouseOrderModule();
        $trans = new Model();
        $trans->startTrans();
        $exOrderId = $billData['ex_order_id'];
        $result = $module->getExWarehouseOrderDetailById($exOrderId);
        if (empty($result)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '出库单信息有误');
        }
        if ($result['examine_status'] != 1) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '已审核的单据不能修改');
        }
        $orderParams = array(
            'ex_order_id' => $exOrderId,
            'remark' => (string)$billData['remark'],
            'real_total_amount' => 0,
        );
        $existGoodsData = $result['goods_data'];
        $goodsData = $billData['goods_data'];
        foreach ($goodsData as $goodsVal) {
            if (empty($goodsVal['id'])) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '商品信息有误');
            }
            if ((float)$goodsVal['actual_delivery_quantity'] < 0) {
                $trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '实际出库数量不得小于0');
            }
            foreach ($existGoodsData as $existGoodsVal) {
                if ($existGoodsVal['id'] == $goodsVal['id']) {
                    $saveGoodsParams = array(
                        'id' => $goodsVal['id'],
                        'actual_delivery_quantity' => $goodsVal['actual_delivery_quantity'],
                        'real_subtotal' => (float)$goodsVal['actual_delivery_quantity'] * (float)$existGoodsVal['unit_price'],
                    );
                    $saveGoodsRes = $module->saveExWarehouseOrderGoods($saveGoodsParams, $trans);
                    if (!$saveGoodsRes) {
                        $trans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败', '商品信息更新失败');
                    }
                    $orderParams['real_total_amount'] += $saveGoodsParams['real_subtotal'];
                }
            }
        }
        $saveOrderRes = $module->saveExWarehouseOrder($orderParams, $trans);
        if (!$saveOrderRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '更新失败', '单据信息修改失败');
        }
        $tableActionModule = new TableActionLogModule();
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_ex_warehouse_order',
            'dataId' => $exOrderId,
            'actionUserId' => $billData['user_id'],
            'actionUserName' => $billData['user_name'],
            'fieldName' => 'examine_status',
            'fieldValue' => 1,
            'remark' => '出库单更新',
        ];
        $logRes = $tableActionModule->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "更新失败", "出库单日志记录失败");
        }
        $trans->commit();
        return returnData(true);
    }

    /**
     * 出库单-单据列表
     * @param array $params
     * -int shopId 门店id
     * -int pagetype 出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
     * -int examine_status 审核状态[1:未审核出库|2:已审核出库|3：拒绝审核]
     * -date bill_date_start 制单日期区间-开始日期
     * -date bill_date_end 制单日期-结束日期
     * -string number_or_creater 单号/制单人
     * -string relation_order_number 关联单号
     * -int page 页码
     * -int pageSize 分页条数
     * @return array
     * */
    public function getExWarehouseOrderList(array $params)
    {
        $module = new ExWarehouseOrderModule();
        $result = $module->getExWarehouseOrderList($params);
        return returnData($result);
    }

    /**
     * 出库单-单据详情
     * @param int $ex_order_id 单据id
     * @param int $export 导出(0:否 1:是)
     * @return array
     * */
    public function getExWarehouseOrderDetail(int $ex_order_id, $export = 0)
    {
        $module = new ExWarehouseOrderModule();
        $result = $module->getExWarehouseOrderDetailById($ex_order_id);
        if ($export == 1) {
            $this->exportExWarehouseOrderDetail($result);
        }
        return returnData($result);
    }

    /**
     * 出库单-出库单详情-导出
     * @param array $data 出库单详情
     * */
    public function exportExWarehouseOrderDetail(array $data)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/ex_warehouse_order.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 6;
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 2, $data['number']);//单号
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 3, $data['pagetype_name']);//单据类型
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 4, $data['remark']);//单据备注
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 2, $data['ex_warehouse_datetime']);//出库时间
        $objPHPExcel->getActiveSheet()->setCellValue('E' . 3, $data['user_name']);//制单人
        $objPHPExcel->getActiveSheet()->setCellValue('H' . 2, $data['merchant_name']);//仓库
        $objPHPExcel->getActiveSheet()->setCellValue('H' . 3, $data['relation_order_number']);//关联单号
        $goodsData = $data['goods_data'];
        $outNum = 0;//出库数量
        $outAmount = 0;//出库金额
        $realOutNum = 0;//实际出库数量
        $realOutAmount = 0;//实际出库金额
        foreach ($goodsData as $info) {
            $outNum += $info['nums'];
            $outAmount += $info['subtotal'];
            $realOutNum += $info['actual_delivery_quantity'];
            $realOutAmount += $info['real_subtotal'];
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $info['goods_name']);//商品名称
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $info['goods_specs_string']);//规格
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $info['remark']);//商品描述
            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $info['goods_unit']);//商品单位
            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $info['unit_price']);//出库单价
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $info['nums']);//出库数量
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $info['subtotal']);//出库金额
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $info['actual_delivery_quantity']);//实际出库数量
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $info['real_subtotal']);//实际出库金额
        }
        //总计
        $allKeyTag = count($goodsData) + 6 + 1;
        $objPHPExcel->getActiveSheet()->setCellValue('E' . $allKeyTag, '总计');
        $objPHPExcel->getActiveSheet()->setCellValue('F' . $allKeyTag, $outNum);//出库数量
        $objPHPExcel->getActiveSheet()->setCellValue('G' . $allKeyTag, $outAmount);//出库金额
        $objPHPExcel->getActiveSheet()->setCellValue('H' . $allKeyTag, $realOutNum);//实际出库
        $objPHPExcel->getActiveSheet()->setCellValue('I' . $allKeyTag, $realOutAmount);//实际出库金额
        $savefileName = '出库单_' . $data['number'];
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 出库单-商品列表
     * @param array $params
     * -int shopId 门店id
     * -int pagetype 出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
     * -int examine_status 审核状态[1:未审核出库|2:已审核出库|3：拒绝审核]
     * -date warehouse_date_start 出库日期区间-开始日期
     * -date warehouse_date_end 出库日期区间-结束日期
     * -string goods_keywords 商品关键字(商品名称/编码)
     * -string relation_order_number 关联单号
     * -int cat_id 分类id
     * -int page 页码
     * -int pageSize 分页条数
     * -int export 导出(0:否 1:是)
     * @return array
     * */
    public function getExWarehouseGoods(array $params)
    {
        $module = new ExWarehouseOrderModule();
        $usePage = 1;//是否使用分页(0:不使用 1:使用)
        if ($params['export'] == 1) {
            $usePage = 0;
        }
        $result = $module->getExWarehouseGoods($params, $usePage);
        if ($params['export'] == 1) {//导出
            $this->exportExWarehouseGoods($params, $result);
        }
        return returnData($result);
    }

    /**
     * 出库单-商品列表-导出
     * @param array $params 筛选条件
     * @param array $result 出库单商品列表数据
     * */
    public function exportExWarehouseGoods(array $params, array $result)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $inputFileName = WSTRootPath() . '/Public/template/ex_warehouse_goods.xlsx';//excel文件路径
        $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
        $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
        $keyTag = 4;
        $warehouseDateStart = (string)$params['warehouse_date_start'];
        $warehouseDateEnd = (string)$params['warehouse_date_end'];
        $startEndDate = '';
        if (!empty($warehouseDateStart)) {
            $startEndDate .= $warehouseDateStart . ' - ';
        }
        if (!empty($warehouseDateEnd)) {
            $startEndDate .= ' - ' . $warehouseDateEnd;
        }
        if (!empty($warehouseDateStart) && !empty($warehouseDateEnd)) {
            $startEndDate = $warehouseDateStart . ' - ' . $warehouseDateEnd;
        }
        $shopName = '';
        if (!empty($result)) {
            $shopName = $result[0]['shopName'];
        }
        $objPHPExcel->getActiveSheet()->setCellValue('B' . 2, $startEndDate);//搜索的数据日期区间
        $objPHPExcel->getActiveSheet()->setCellValue('K' . 2, $shopName);//仓库名称
        $outNum = 0;//出库数量
        $outAmount = 0;//出库金额
        $realOutNum = 0;//实际出库数量
        $realOutAmount = 0;//实际出库金额
        foreach ($result as $info) {
            $outNum += $info['nums'];
            $outAmount += $info['subtotal'];
            $realOutNum += $info['actual_delivery_quantity'];
            $realOutAmount += $info['real_subtotal'];
            $keyTag += 1;
            $objPHPExcel->getActiveSheet()->setCellValue('A' . $keyTag, $info['goods_name']);//商品名称
            $objPHPExcel->getActiveSheet()->setCellValue('B' . $keyTag, $info['goodsSn']);//商品编码
            $objPHPExcel->getActiveSheet()->setCellValue('C' . $keyTag, $info['goods_specs_string']);//商品规格
//            $objPHPExcel->getActiveSheet()->setCellValue('D' . $keyTag, $info['shopCatId1Name']);//门店一级分类
//            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, $info['shopCatId2Name']);//门店二级分类
            $objPHPExcel->getActiveSheet()->mergeCells("D{$keyTag}:E{$keyTag}");
            $objPHPExcel->getActiveSheet()->setCellValue("D{$keyTag}", $info['cat_name_merge']);
            $objPHPExcel->getActiveSheet()->setCellValue('F' . $keyTag, $info['remark']);//商品描述
            $objPHPExcel->getActiveSheet()->setCellValue('G' . $keyTag, $info['goods_unit']);//商品单位
            $objPHPExcel->getActiveSheet()->setCellValue('H' . $keyTag, $info['pagetype_name']);//出库类型
            $objPHPExcel->getActiveSheet()->setCellValue('I' . $keyTag, $info['customer_username']);//客户名称
            $objPHPExcel->getActiveSheet()->setCellValue('J' . $keyTag, $info['number']);//关联单位
            $objPHPExcel->getActiveSheet()->setCellValue('K' . $keyTag, $info['ex_warehouse_datetime']);//出库日期
            $objPHPExcel->getActiveSheet()->setCellValue('L' . $keyTag, $info['unit_price']);//出库单价
            $objPHPExcel->getActiveSheet()->setCellValue('M' . $keyTag, $info['nums']);//出库数量
            $objPHPExcel->getActiveSheet()->setCellValue('N' . $keyTag, $info['subtotal']);//出库金额
            $objPHPExcel->getActiveSheet()->setCellValue('O' . $keyTag, $info['actual_delivery_quantity']);//实际出库数量
            $objPHPExcel->getActiveSheet()->setCellValue('P' . $keyTag, $info['real_subtotal']);//实际出库金额
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $keyTag, $info['remark']);//单据备注
        }
        //总计
        $allKeyTag = count($result) + 4 + 1;
        $objPHPExcel->getActiveSheet()->setCellValue('L' . $allKeyTag, '总计');
        $objPHPExcel->getActiveSheet()->setCellValue('M' . $allKeyTag, $outNum);//出库数量
        $objPHPExcel->getActiveSheet()->setCellValue('N' . $allKeyTag, $outAmount);//出库金额
        $objPHPExcel->getActiveSheet()->setCellValue('O' . $allKeyTag, $realOutNum);//实际出库
        $objPHPExcel->getActiveSheet()->setCellValue('P' . $allKeyTag, $realOutAmount);//实际出库金额
        $savefileName = '出库查询导出' . date('Ymdhis');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $savefileName . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 出库单-删除
     * @param string $ex_order_id_str 出库单id,多个用英文逗号分隔
     * @return array
     * */
    public function deleteExWarehouseOrder($ex_order_id_str)
    {
        $module = new ExWarehouseOrderModule();
        $ex_order_id_arr = explode(',', $ex_order_id_str);
        foreach ($ex_order_id_arr as $ex_order_id) {
            $detail = $module->getExWarehouseOrderDetailById($ex_order_id);
            $examine_status = $detail['examine_status'];
            if (!in_array($examine_status, array(1, 3))) {
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败，已出库的单据不能删除');
            }
        }
        $result = $module->deleteExWarehouseOrder($ex_order_id_str);
        if (!$result) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        return returnData(true);
    }
}