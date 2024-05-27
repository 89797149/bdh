<?php
/**
 * 采购相关
 * Created by PhpStorm.
 * User: YHJ
 * Date: 2021-07-10
 * Time: 11:57
 */

namespace Merchantapi\Model;


use App\Enum\ExceptionCodeEnum;
use App\Models\PurchaseGoodsModel;
use App\Modules\Goods\GoodsCatModule;
use App\Modules\Goods\GoodsModule;
use App\Modules\JxcGoods\JxcGoodsModule;
use App\Modules\Purchase\PurchaseModule;
use Think\Model;

class PurchaseModel extends BaseModel
{
    /**
     * 采购-订单商品采购列表
     * @param array $params
     * -int shop_id 门店id
     * -string orderNo 订单号
     * -datetime ordertime_start 下单时间-开始时间
     * -datetime ordertime_end 下单时间-结束时间
     * -date require_date 期望送达日期
     * -int cat_id 店铺分类id
     * -int page 页码
     * -int pageSize 分页条数
     * -int  分页条数
     * -int export 是否导出(0:否 1:是)
     * @return array
     * */
    public function getPurchaseGoodsList(array $params)
    {
        $purchase_module = new PurchaseModule();
        $params['isNeedMerge'] = 1;
        $result = $purchase_module->getPurchaseGoodsList($params);
        if ($params['export'] != 1) {
            return $result;
        }
        $this->exportPurchaseGoods($result, $params);
    }

    /**
     * 采购-订单商品采购列表-导出
     * @param array $result 订单商品采购列表数据
     * @param array 订单采购列表中接收的参数
     * */
    public function exportPurchaseGoods(array $result, array $params)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        //引入Excel类
        $objPHPExcel = new \PHPExcel();
        // 设置excel文档的属性
        $objPHPExcel->getProperties()->setCreator("cyf")
            ->setLastModifiedBy("cyf Test")
            ->setTitle("goodsList")
            ->setSubject("Test1")
            ->setDescription("Test2")
            ->setKeywords("Test3")
            ->setCategory("Test result file");
        //设置excel工作表名及文件名
        $title = '订单商品采购列表';
        $excel_filename = '订单商品采购列表_' . date('Ymd_His');
        // 操作第一个工作表
        $objPHPExcel->setActiveSheetIndex(0);
        //第一行设置内容
        $objPHPExcel->getActiveSheet()->setCellValue('A1', $excel_filename);
        //合并
        $objPHPExcel->getActiveSheet()->mergeCells('A1:F1');
        //设置单元格内容加粗
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);
        //设置单元格内容水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置excel的表头
        $sheet_title = array('序号', '商品店铺分类', '商品名称', '商品价格', '规格', '数量', '智能备注');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G');
        //首先是赋值表头
        for ($k = 0; $k < count($letter); $k++) {
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k] . '2', $sheet_title[$k]);
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getFont()->setSize(10)->setBold(true);
            //设置单元格内容水平居中
            $objPHPExcel->getActiveSheet()->getStyle($letter[$k] . '2')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            //设置每一列的宽度
            $objPHPExcel->getActiveSheet()->getColumnDimension($letter[$k])->setWidth(40);
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(30);
        }
        //开始赋值
        for ($i = 0; $i < count($result); $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $result[$i];
            for ($j = 0; $j < count($letter); $j++) {
                switch ($j) {
                    case 0 :
                        //序号
                        $cellvalue = $temp['key_id'];
                        break;
                    case 1 :
                        //商品编号
                        $cellvalue = $temp['shopCat1Name'] . '/' . $temp['shopCat2Name'];
                        break;
                    case 2 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 3 :
                        //价格
                        $cellvalue = $temp['goodsPrice'];
                        break;
                    case 4 :
                        //规格
                        $cellvalue = $temp['skuSpecAttr'];
                        break;
                    case 5 :
                        //数量
                        $cellvalue = $temp['goodsNums'];
                        break;
                    case 6 :
                        //智能备注
                        $cellvalue = $temp['remarks'];
                        break;
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(40);
        }
//        unset($res);
        //赋值结束，开始输出
        $objPHPExcel->getActiveSheet()->setTitle($title);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $excel_filename . '.xls"');
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
    }

    /**
     * 采购-订单商品采购列表-删除
     * @param int $order_id 订单id
     * @param int $goods_id 商品id
     * @param array $purchase_goods_where 一键采购页的搜索条件
     * @param int $sku_id skuId
     * @return array
     * */
    public function delPurchaseGoods(int $goods_id, int $sku_id, array $purchase_goods_where)
    {
        $module = new PurchaseModule();
        $rep_params = $purchase_goods_where;
        $rep_params['isNeedMerge'] = 0;
        $rep_params['onekeyPurchase'] = 1;
        $rep_params['goods_id'] = $goods_id;
        $rep_params['sku_id'] = $sku_id;
        $purchase_goods_list = $module->getPurchaseGoodsList($rep_params);
        if (empty($purchase_goods_list)) {
            return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
        }
        $db_trans = new Model();
        $db_trans->startTrans();
        foreach ($purchase_goods_list as $item) {
            $order_id = $item['orderId'];
            $goods_id = $item['goodsId'];
            $sku_id = $item['skuId'];
            $res = $module->delPurchaseGoodsByParams($order_id, $goods_id, $sku_id, $db_trans);
            if (!$res) {
                $db_trans->rollback();
                return returnData(false, ExceptionCodeEnum::FAIL, 'error', '删除失败');
            }
        }
        $db_trans->commit();
        return returnData(true);
    }

    /**
     * 一键采购-订单商品采购列表 PS:总仓商品数据
     * @param array $params
     * -int shop_id 门店id
     * -string orderNo 订单号
     * -datetime ordertime_start 下单时间-开始时间
     * -datetime ordertime_end 下单时间-结束时间
     * -date require_date 期望送达日期
     * -int cat_id 店铺分类id
     * -string statusMark 订单状态【toBePaid:待付款|toBeAccepted:待接单|toBeDelivered:待发货|toBeReceived:待收货|toBePickedUp:待取货|confirmReceipt:已完成|takeOutDelivery:外卖配送|invalid:无效订单(用户取消|商家拒收)】,不传默认全部
     * @return array
     * */
    public function getPurchaseJxcGoodsList(array $params)
    {
        //这个方法是已经存在的,以下逻辑皆是按着以前的代码逻辑写的
        $purchase_module = new PurchaseModule();
        $params['onekeyPurchase'] = 1;
        $params['isNeedMerge'] = 1;
        $purchase_goods_list = $purchase_module->getPurchaseGoodsList($params);
//        $purchase_goods_list = $this->getPurchaseGoodsList($params);
        if (empty($purchase_goods_list)) {
            return array();
        }
        $goods_module = new GoodsModule();
        $jxc_goods_module = new JxcGoodsModule();
        $goods_id_arr = array_column($purchase_goods_list, 'goodsId');
        $return_list = array();
        foreach ($goods_id_arr as $goods_id) {
            $sku_list = array();
            foreach ($purchase_goods_list as $pur_detail) {
                if ($goods_id != $pur_detail['goodsId']) {
                    continue;
                }
                $local_goodsid = $pur_detail['goodsId'];
                $local_skuid = $pur_detail['skuId'];
                $local_goods_data = $goods_module->getGoodsInfoById($local_goodsid, 'goodsId,goodsSn', 2);
                if (empty($local_goods_data)) {
                    continue;
                }
                $jxc_sku_data = array();
                if ($local_skuid > 0) {
                    $local_sku_data = $goods_module->getSkuSystemInfoById($local_skuid, 2);
                    if (empty($local_sku_data)) {
                        continue;
                    }
                    $jxc_sku_data = $jxc_goods_module->getGoodsSkuDetailByCode($local_goods_data['skuBarcode']);
                    if ($jxc_sku_data) {
                        continue;
                    }
                    $jxc_sku_data['totalNum'] = $pur_detail['goodsNums'];
                    $jxc_sku_data['remarks'] = '';
                    $jxc_sku_data['buyPirce'] = $jxc_sku_data['sellPrice'];
                }
                $jxc_goods_where = array(
                    'goodsSn' => $local_goods_data['goodsSn'],
                );
                $jxc_goods_data = $jxc_goods_module->getGoodsDetailByParams($jxc_goods_where);
                if (empty($jxc_goods_data)) {
                    continue;
                }
                if (!isset($return_list[$goods_id])) {
                    //不太清楚,以前的结构就是这样的
                    $return_info = array();
                    $return_info['orderId'] = $pur_detail['orderId'];
                    $return_info['skuId'] = $pur_detail['skuId'];
                    $return_info['goodsId'] = $jxc_goods_data['goodsId'];
                    $return_info['isOrder'] = 1;
                    $return_info['remark'] = '';
                    $return_info['sku'] = $sku_list;
                    $return_info['totalNum'] = 0;
                    $return_info['wstGoodsId'] = $local_goods_data['goodsId'];
                    $return_info['goodsInfo'] = array(
                        'buyPirce' => $jxc_goods_data['sellPrice'],
                        'goodsImg' => $pur_detail['goodsThums'],
                        'goodsName' => $pur_detail['goodsName'],
                        'goodsSn' => $jxc_goods_data['goodsSn'],
                    );
                    $return_list[$goods_id] = $return_info;
                }
                $return_list[$goods_id]['totalNum'] += $pur_detail['goodsNums'];
                if ($local_skuid > 0) {//存在sku
                    if (!isset($return_list[$goods_id]['sku'][$local_skuid])) {
                        $return_list[$goods_id]['sku'][] = $jxc_sku_data;
                    }
                }
            }
            if (!empty($return_list[$goods_id])) {
                $return_list[$goods_id]['sku'] = (array)array_values($return_list[$goods_id]['sku']);
            }
        }
        return (array)array_values($return_list);
    }
}