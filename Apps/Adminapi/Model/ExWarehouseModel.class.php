<?php
/**
 * 出库单
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-08-06
 * Time: 17:28
 */

namespace Adminapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Modules\ExWarehouse\ExWarehouseOrderModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\Log\TableActionLogModule;
use Think\Model;

class ExWarehouseModel extends BaseModel
{
    /**
     * 出库单-单据列表
     * @param array $params
     * -string shop_keywords 店铺关键字(名称/编号)
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
        $result['shopName'] = $result['merchant_name'];
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
     * -int shop_keywords 门店关键字(名称/编号)
     * -int pagetype 出库单据类型[1销售出库|2其他出库|3调货出库|4采购退货|5单位转换|6报损或盘损出库]
     * -int examine_status 审核状态[1:未审核出库|2:已审核出库|3：拒绝审核]
     * -date warehouse_date_start 出库日期区间-开始日期
     * -date warehouse_date_end 出库日期区间-结束日期
     * -string goods_keywords 商品关键字(商品名称/编码)
     * -string relation_order_number 关联单号
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
        //ini_set('memory_limit', '-1');
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
            $objPHPExcel->getActiveSheet()->mergeCells("D{$keyTag}:E{$keyTag}");
            $objPHPExcel->getActiveSheet()->setCellValue("D{$keyTag}", $info['cat_name_merge']);
//            $objPHPExcel->getActiveSheet()->setCellValue("D{$keyTag}", $info['cat_name_merge']);//分类名称
//            $objPHPExcel->getActiveSheet()->setCellValue('E' . $keyTag, '');
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
            $objPHPExcel->getActiveSheet()->setCellValue('Q' . $keyTag, $info['bill_remark']);//单据备注
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
     * 出库单-审核
     * @param int $loginUserInfo 登陆者信息
     * @param int $exOrderId 出库单
     * @param int $status 审核操作(-1:拒绝出库 1:审核通过)
     * @return array
     * */
    public function examineExWarehouseOrder(array $loginUserInfo, int $exOrderId, int $status)
    {
        $module = new ExWarehouseOrderModule();
        $detail = $module->getExWarehouseOrderDetailById($exOrderId);
        if (empty($detail)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "数据有误");
        }
        if ($detail['examine_status'] != 1) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "已审核的单据不能重复审核");
        }
        $trans = new Model();
        $trans->startTrans();
        $orderParams = array(
            'ex_order_id' => $exOrderId,
            'auditinguser_id' => $loginUserInfo['user_id'],
            'auditingtime' => date('Y-m-d H:i:s'),
        );
        $logParams = [//写入状态变动记录表
            'tableName' => 'wst_ex_warehouse_order',
            'dataId' => $exOrderId,
            'actionUserId' => $loginUserInfo['user_id'],
            'actionUserName' => $loginUserInfo['user_username'],
            'fieldName' => 'examine_status',
            'fieldValue' => 2,
            'remark' => '出库单已通过审核',
        ];
        if ($status == 1) {//审核通过,出库
            $orderParams['examine_status'] = 2;
            $orderParams['ex_warehouse_datetime'] = date('Y-m-d H:i:s');
            $goodsData = $detail['goods_data'];
            $goodsModule = new GoodsModule();
            foreach ($goodsData as $goodsVal) {
                $goodsId = $goodsVal['goods_id'];
                $skuId = $goodsVal['sku_id'];
                $outStock = (float)$goodsVal['actual_delivery_quantity'];
                if ($outStock > 0) {
                    $stockRes = $goodsModule->deductionGoodsStock($goodsId, $skuId, $outStock, 1, 1, $trans);
                    if ($stockRes['code'] != ExceptionCodeEnum::SUCCESS) {
                        $trans->rollback();
                        return returnData(false, ExceptionCodeEnum::FAIL, 'error', '出库单审核失败', '商品库存更新失败');
                    }
                }
            }
        }
        if ($status == -1) {//拒绝出库
            $logParams['remark'] = '出库单已被拒绝';
            $orderParams['examine_status'] = 3;
        }
        $logParams['fieldValue'] = $orderParams['examine_status'];
        $tableActionModule = new TableActionLogModule();
        $logRes = $tableActionModule->addTableActionLog($logParams, $trans);
        if (!$logRes) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单审核失败", "出库单日志记录失败");
        }
        $updateOrderRes = $module->saveExWarehouseOrder($orderParams, $trans);
        if (empty($updateOrderRes)) {
            $trans->rollback();
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', "出库单审核失败", "出库单状态更新失败");
        }
        $trans->commit();
        return returnData(true);
    }

}