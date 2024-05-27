<?php

namespace Merchantapi\Model;

use App\Models\OrderComplainsrecordModel;
use App\Modules\Goods\GoodsModule;
use App\Modules\Orders\OrdersModule;
use App\Modules\Report\ReportModule;

/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 报表类
 */
class ReportModel extends BaseModel
{


    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * 获取营业数据列表---报表统计总表
     */
//    public function businessList($startDate,$endDate,$shopId)
//    {
//        $page = (int)I('page',1);
//        $pageSize= (int)I('pageSize',15);
//        $limitPate = ($page-1)*$pageSize;
//        $where = " `reportDate` BETWEEN '{$startDate}' AND '{$endDate}'";
//        $where .= " and shopId = {$shopId}";
//        $sql = " select * from `wst_order_report` where ".$where;
//        $data = $this->pageQuery($sql, $page, $pageSize);
//        $field = "sum(a.salesRefundOrderNum) as salesRefundOrderNumCount,sum(a.salesRefundOrderMoney) as salesRefundOrderMoneyCount,sum(a.salesOrderNum) as salesOrderNumCount,sum(a.salesOrderMoney) as salesOrderMoneyCount,sum(a.procureOrderNum) as procureOrderNumCount,sum(a.procureOrderMoney) as procureOrderMoneyCount";
//        $sqlSum = " select {$field} from (select * from `wst_order_report`where {$where} limit $limitPate,$pageSize) as a";
//        $sumCount = $this->query($sqlSum);
//        $sum = [];
//        $sum['salesRefundOrderNumCount'] = formatAmount($sumCount[0]['salesRefundOrderNumCount'],0);//订单退款数量
//        $sum['salesRefundOrderMoneyCount'] = formatAmount($sumCount[0]['salesRefundOrderMoneyCount']);//订单退款金额
//        $sum['salesOrderNumCount'] = formatAmount($sumCount[0]['salesOrderNumCount'],0);//销售订单数量
//        $sum['salesOrderMoneyCount'] = formatAmount($sumCount[0]['salesOrderMoneyCount']);//销售订单总金额
//        $sum['procureOrderNumCount'] = formatAmount($sumCount[0]['procureOrderNumCount'],0);//采购订单数量
//        $sum['procureOrderMoneyCount'] = formatAmount($sumCount[0]['procureOrderMoneyCount']);//采购订单总金额
//        $data['sum'] = $sum;
//        return $data;
//    }
    /**
     * PS:该方法由以上注释的方法基础上所来
     * @param $startDate 时间区间-开始时间
     * @param $endDate 时间区间-结束时间
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param $shopId 门店id
     * @return array
     * 获取营业数据列表---报表统计总表
     */
    public function businessList($startDate, $endDate, $page, $pageSize, $shopId)
    {
        //在原来的基础上更正结果集,所以返回字段全部用以前已有的字段
        $limitPate = ($page - 1) * $pageSize;
        $where = "shopId={$shopId} and reportDate between '{$startDate}' and '{$endDate}' ";
        $sql = " select * from `wst_order_report` where " . $where;
        $data = $this->pageQuery($sql, $page, $pageSize);
        foreach ($data['root'] as &$item) {
            $item['practicalPrice'] = bc_math($item['salesOrderMoney'], $item['salesRefundOrderMoney'], 'bcsub', 2);
        }
        unset($item);
        $field = "sum(a.salesRefundOrderNum) as salesRefundOrderNumCount,sum(a.salesRefundOrderMoney) as salesRefundOrderMoneyCount,sum(a.salesOrderNum) as salesOrderNumCount,sum(a.salesOrderMoney) as salesOrderMoneyCount,sum(a.procureOrderNum) as procureOrderNumCount,sum(a.procureOrderMoney) as procureOrderMoneyCount";
        $sqlSum = " select {$field} from (select * from `wst_order_report`where {$where} limit $limitPate,$pageSize) as a";
        $sumCount = $this->query($sqlSum);
        $sum = [];
        $sum['salesRefundOrderNumCount'] = formatAmount($sumCount[0]['salesRefundOrderNumCount'], 0);//订单退款数量
        $sum['salesRefundOrderMoneyCount'] = formatAmount($sumCount[0]['salesRefundOrderMoneyCount']);//订单退款金额
        $sum['salesOrderNumCount'] = formatAmount($sumCount[0]['salesOrderNumCount'], 0);//销售订单数量
        $sum['salesOrderMoneyCount'] = formatAmount($sumCount[0]['salesOrderMoneyCount']);//销售订单总金额
        $sum['procureOrderNumCount'] = formatAmount($sumCount[0]['procureOrderNumCount'], 0);//采购订单数量
        $sum['procureOrderMoneyCount'] = formatAmount($sumCount[0]['procureOrderMoneyCount']);//采购订单总金额
        $data['sum'] = $sum;
        return $data;
    }

    /**
     * PS:该方法由以上注释的方法基础上所来
     * @param $startDate 时间区间-开始时间
     * @param $endDate 时间区间-结束时间
     * @param int page 页码
     * @param int pageSize 分页条数
     * @param $shopId 门店id
     * @return array
     * 获取营业数据列表---报表统计总表
     */
    public function businessListTwo($startDate, $endDate, $page, $pageSize, $shopId)
    {
        //复制上面的代码,主逻辑不变,只是数据源临时获取,上面的方式得出的数据结果无法满足客户需求
        //这里只统计订单相关!产品设计之初就未考虑到其他的购买金额统计,目前只有订单统计中统计了其他购买金额
        $where = "shopId={$shopId} and reportDate between '{$startDate}' and '{$endDate}' ";
        $sql = " select * from `wst_order_report` where " . $where;
        $data = $this->pageQuery($sql, $page, $pageSize);
        $list = $data['root'];
        $pay_start_time = $startDate . ' 00:00:00';
        $pay_end_time = $endDate . ' 23:59:59';
        $orders_tab = M('orders');
        $order_list = $orders_tab->where(array(
            'shopId' => $shopId,
            'pay_time' => array('between', array($pay_start_time, $pay_end_time)),
            'isPay' => 1
        ))->select();
        $time_order_list_map = [];
        $salesOrderMoneyCount = 0; //订单销售金额
        $salesRefundOrderNumCount = 0;//订单退款数量
        $salesRefundOrderMoneyCount = 0;//订单退款金额
        foreach ($order_list as $order_list_row) {
            //$order_list_map[$order_list_row['orderId']] = $order_list_row;
            $order_pay_time = date('Y-m-d', strtotime($order_list_row['pay_time']));
            $order_list_row['pay_time'] = $order_pay_time;
            $time_order_list_map[$order_pay_time][] = $order_list_row;
            $salesOrderMoneyCount = bc_math($salesOrderMoneyCount, $order_list_row['realTotalMoney'], 'bcadd', 2);
        }
        foreach ($list as $key => $list_row) {
            $salesRefundOrderNumCount = bc_math($salesRefundOrderNumCount, $list_row['salesRefundOrderNum'], 'bcadd', 0); //这个和订单统计模块保持一致
            $salesRefundOrderMoneyCount = bc_math($salesRefundOrderMoneyCount, $list_row['salesRefundOrderMoney'], 'bcadd', 2); //这个和订单统计模块保持一致
            $curr_time_orders = $time_order_list_map[$list_row['reportDate']];
            $sales_order_money_arr = array_column($curr_time_orders, 'realTotalMoney');
            $list_row['salesOrderNum'] = count($curr_time_orders);
            $list_row['salesOrderMoney'] = array_sum($sales_order_money_arr);
            $list_row['practicalPrice'] = bc_math($list_row['salesOrderMoney'], $list_row['salesRefundOrderMoney'], 'bcsub', 2);
            $list[$key] = $list_row;
        }
        $sum = [];
        $sum['salesRefundOrderNumCount'] = $salesRefundOrderNumCount;//订单退款数量
        $sum['salesRefundOrderMoneyCount'] = $salesRefundOrderMoneyCount;//订单退款金额
        $sum['salesOrderNumCount'] = count($order_list);//销售订单数量
        $sum['salesOrderMoneyCount'] = $salesOrderMoneyCount;//销售订单总金额
        $data['sum'] = $sum;
        $data['root'] = $list;
        return $data;
    }

    /**
     * @param $reportId 报表id
     * @param $shopId
     * @return mixed
     * 获取营业数据详情----按分类查看
     */
    public function businessDetail($reportId, $shopId)
    {
        $m = M('sales_order_report sor');
        $field = "sor.*,wgc3.catName";
        $where = [];
        $where['sor.reportId'] = $reportId;
        $where['sor.otpId'] = ['elt', 0];
        $where['sor.shopId'] = $shopId;

//        $data = $m->where(array(
//            'sor.reportId' => $reportId,
//            'sor.shopId' => $shopId,
//            'sor.otpId' => array('elt', 0),
//        ))->join('left join wst_goods_cats wgc ON wgc.catId = sor.goodsCatId3')->field($field)->select();

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);


        $res = [];
        foreach ($data as $v) {
            $res[$v['goodsCatId3']][] = $v;
        }
        $res = array_values($res);
        $arr = [];
        $array = [];
        $total_price = 0;//商品实际金额统计
        foreach ($res as $v) {
            $goodsCostPrice = 0;//进货价
            $goodsPaidPrice = 0;//商品实付金额
            $refundFee = 0;//退款金额
            foreach ($v as $val) {
                // var_dump($val);
                // $goodsCostPrice += bc_math($val['goodsCostPrice'], $val['goodsNums'], 'bcmul', 2);
                $goodsCostPrice += bc_math($val['goodsPrice'], $val['goodsNums'], 'bcmul', 2);

                $goodsPaidPrice += $val['goodsPaidPrice'];
                // $total_price += bc_math($val['goodsCostPrice'], $val['goodsNums'], 'bcmul', 2);
                $total_price += bc_math($val['goodsPrice'], $val['goodsNums'], 'bcmul', 2);
                //  总金额去除退款金额
                $total_price -= $val['refundFee'];


                $refundFee += $val['refundFee'];
                $catName = $val['catName'];
            }
            $array['catName'] = $catName;
            $array['goodsCostPriceCount'] = formatAmount($goodsCostPrice);
            $array['goodsPaidPriceCount'] = formatAmount($goodsPaidPrice);
            $array['refundFeeCount'] = formatAmount($refundFee);

            // var_dump($goodsCostPrice);

            // var_dump($goodsCostPrice, $goodsPaidPrice);
            $array['percent'] = (bc_math($goodsCostPrice, $goodsPaidPrice, 'bcdiv', 4) * 100) . '%';
            $arr[] = $array;
        }
        $list = [];
        $list['list'] = $arr;
        $list['total_price'] = formatAmount($total_price);
        return $list;
    }


    /**
     * @param $reportId 报表id
     * @param $shopId
     * @return mixed
     * 获取营业数据详情----按分类查看
     */
    public function businessDetailTwo($reportId, $shopId)
    {
        //屎
        //复制上面的代码进行修改,上面代码的运行结果不是客户想要的,这里临时处理下
        $report_tab = M('order_report');
        $orders_tab = M('orders');
        $order_complainsrecord_tab = M('order_complainsrecord');
        $goods_pricediffe_tab = M('goods_pricediffe');
        $goods_tab = M('goods');
        $report_where = [
            'reportId' => $reportId,
            'shopId' => $shopId,
        ];
        $report_row = $report_tab->where($report_where)->find();
        $pay_start_time = $report_row['reportDate'] . ' 00:00:00';
        $pay_end_time = $report_row['reportDate'] . ' 23:59:59';
        $order_list = (array)$orders_tab->where(array(
            'shopId' => $shopId,
            'pay_time' => ['between', [$pay_start_time, $pay_end_time]],
            'isPay' => 1
        ))->select();
        $order_id_arr = array_column($order_list, 'orderId');
        $order_goods_where = [
            'orderId' => ['in', $order_id_arr],
        ];
        $order_goods_list = M('order_goods')->where($order_goods_where)->select();
        $order_goods_goods_id_arr = array_column($order_goods_list, 'goodsId');
        $complainsrecord_where = [
            "addTime" => ['between', [$pay_start_time, $pay_end_time]],
            "complainId" => ['GT', 0]
        ];
        $goods_complainsrecord_list = (array)$order_complainsrecord_tab->where($complainsrecord_where)->select();
        $goods_id_arr_merge = $order_goods_goods_id_arr;//所有相关的商品ID汇总
        if (!empty($goods_complainsrecord_list)) {
            $goods_complainsrecord_goods_id = array_column($goods_complainsrecord_list, 'goodsId');
            $goods_id_arr_merge = array_merge($goods_id_arr_merge, $goods_complainsrecord_goods_id);
        }
        $goods_pricediffe_where = [
            'payTime' => ['between', [$pay_start_time, $pay_end_time]],
            'isPay' => 1,
        ];
        $goods_pricediffe_list = $goods_pricediffe_tab->where($goods_pricediffe_where)->select();
        if (!empty($goods_pricediffe_list)) {
            $goods_pricediffe_goods_id = array_column($goods_pricediffe_list, 'goodsId');
            $goods_id_arr_merge = array_merge($goods_id_arr_merge, $goods_pricediffe_goods_id);
        }
        $goods_id_arr_merge = array_unique($goods_id_arr_merge);
        $goods_where = [
            'goodsId' => ['in', $goods_id_arr_merge],
        ];
        $goods_list = $goods_tab->where($goods_where)->field('goodsId,goodsName,goodsCatId3')->select();
        $goods_list_map = [];
        foreach ($goods_list as $key => $goods_list_row) {
            $goods_list_map[$goods_list_row['goodsId']] = $goods_list_row;
        }
        $goods_catid_3_arr = array_column($goods_list, 'goodsCatId3');
        $goods_catid_3_arr = array_unique($goods_catid_3_arr);
        $goods_cat_tab = M('goods_cats');
        $goods_cat_where = [
            'catId' => ['in', $goods_catid_3_arr],
        ];
        $goods_cat_list = $goods_cat_tab->where($goods_cat_where)->field('catId,catName')->select();
        $goods_cat_list_map = [];
        foreach ($goods_cat_list as $key => $cat_list_row) {
            $cat_list_row['goodsPaidPriceCount'] = (float)0; //商品实付金额
            $cat_list_row['refundFeeCount'] = (float)0; //商品退货金额（商品售后+商品补差价）
            $goods_cat_list_map[$cat_list_row['catId']] = $cat_list_row['catName'];
            foreach ($order_goods_list as $order_goods_list_row) {
                $curr_goods_row = $goods_list_map[$order_goods_list_row['goodsId']];
                if ($curr_goods_row['goodsCatId3'] != $cat_list_row['catId']) {
                    continue;
                }
                $curr_goods_price_total = bc_math($order_goods_list_row['goodsNums'], $order_goods_list_row['goodsPrice'], 'bcmul', 2);
                $cat_list_row['goodsPaidPriceCount'] = bc_math($cat_list_row['goodsPaidPriceCount'], $curr_goods_price_total, 'bcadd', 2);
            }
            foreach ($goods_complainsrecord_list as $goods_complainsrecord_list_row) {
                $complainsrecord_goods_row = $goods_list_map[$order_goods_list_row['goodsId']];
                if ($complainsrecord_goods_row['goodsCatId3'] != $cat_list_row['catId']) {
                    continue;
                }
                $cat_list_row['refundFeeCount'] = bc_math($cat_list_row['refundFeeCount'], $goods_complainsrecord_list_row['money'], 'bcadd', 2);
            }
            foreach ($goods_pricediffe_list as $goods_pricediffe_list_row) {
                $pricediffe_goods_row = $goods_list_map[$goods_pricediffe_list_row['goodsId']];
                if ($pricediffe_goods_row['goodsCatId3'] != $cat_list_row['catId']) {
                    continue;
                }
                $cat_list_row['refundFeeCount'] = bc_math($cat_list_row['refundFeeCount'], $goods_pricediffe_list_row['money'], 'bcadd', 2);
            }
            $goods_cat_list[$key] = $cat_list_row;
        }
        $return_list = $goods_cat_list;
        //s$actual_amount = 0;//实际金额
        $total_goods_paid_price = array_sum(array_column($return_list, 'goodsPaidPriceCount'));
        $total_goods_refund_price = array_sum(array_column($return_list, 'refundFeeCount'));
        $actual_amount = bc_math($total_goods_paid_price, $total_goods_refund_price, 'bcsub', 2);
        $list = [];
        $list['list'] = $return_list;
        $list['total_price'] = formatAmount($actual_amount);
        return $list;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @param $type 1:按商品统计|2：按分类统计
     * @param $profitType
     * @return array
     * @throws \PHPExcel_Exception
     * 获取商品销售列表
     * $profitType 销售毛利状态
     */
    public function commoditySaleList($startDate, $endDate, $type, $profitType = 0, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//1:导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        $where['sor.shopId'] = $shopId;
        if (!empty((int)I('goodsCatId1'))) {
            $where['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $where['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $where['wg.goodsCatId3'] = I('goodsCatId3');
        }
        $keywords = htmlspecialchars_decode(I('keywords'));
        if (!empty($keywords)) {
            $where['wg.goodsName'] = array("like", "%$keywords%");
        }
        $field = "sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

//        $data = M('sales_order_report sor')
//            ->join('left join wst_goods wg ON wg.goodsId = sor.goodsId')
//            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = sor.goodsCatId1')
//            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = sor.goodsCatId2')
//            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = sor.goodsCatId3')
//            ->where($where)
//            ->field($field)
//            ->select();
        foreach ($data as $v) {
            if ($type == 1) {
                $res[$v['goodsId']][] = $v;
            } else {
                $res[$v['goodsCatId3']][] = $v;
            }
        }
        $arr = [];
        $array = [];
        $practicalSum = 0;//所有商品的实际价格
        $orderSum = 0;//所有订单数量
        $refundOrderSum = 0;//退单数量
        $refundGoodsSum = 0;//退货数量
        $marketSum = 0;//所有销售数量
        $goodsCostPriceSum = 0;//所有进货价--成本价
        $profitSum = 0;//所有毛利
        $salesOrderMoney = 0;//销售订单总金额
        $salesRefundOrderMoney = 0;//退货金额
        $refundGoodsCostPriceSum = 0;//退货总成本
        $rank = 0;//综合排名
        $sales_order_report_tab = M('sales_order_report sor');
        $count_order_id_arr = array_unique(array_column($data, 'orderId'));
        $diff_money_total = 0;//补差价金额
        $record_model = new OrderComplainsrecordModel();
        foreach ($res as $v) {
            $goodsNums = 0;//商品数量
            $orderNum = 0;//订单数量
            $goodsPaidPrice = 0;//商品实付金额
            $refundFee = 0;//退款金额
            $refundGoodsCostPrice = 0;//退货成本
            $refundOrderCount = 0;//退款订单数量
            $refundGoodsCount = 0;//退款商品数量
            $goodsPriceNum = 0;//商品实际金额
            $marketNum = 0;//销售数量
            $goodsCostPrice = 0;//进货价--成本价
            $profit = 0;//毛利
            $rank += 1;//综合排名
            $diff_money_signle = 0;//单商品补差价金额
            foreach ($v as $val) {
                $record_info = $record_model->where(array(
                    'orderId' => $val['orderId']
                ))->find();
                if ($record_info) {
                    $diff_money_total += (float)$record_info['money'];
                    $diff_money_signle += (float)$record_info['money'];
                }
                $return_order_count_where = (array)$where;
                //接着上面的where条件续写
                $goodsCatName3 = $val['goodsCatName3'];

                if ($val['is_return_goods'] == 1) {
                    $return_order_count_where['sor.orderId'] = $val['orderId'];
                    $return_order_count_where['sor.goodsId'] = $val['goodsId'];
                    $return_order_count_where['sor.is_return_goods'] = 1;
                    $return_order_count = (int)$sales_order_report_tab->where($return_order_count_where)->group('orderId')->count();
                    $refundOrderCount += $return_order_count;

                    $return_goods_count_where = $where;
                    $return_goods_count_where['sor.is_return_goods'] = 1;
                    $return_goods_count_where['sor.goodsId'] = $val['goodsId'];
                    $return_goods_sum = (int)$sales_order_report_tab->where($return_goods_count_where)->sum('goodsNums');
                    $refundGoodsCount += $return_goods_sum;
                }
                $marketNum += $val['goodsNums'];
                $goodsNum = $val['goodsNums'];
                $goodsNums += $goodsNum;
                $goodsPaidPrice += $val['goodsPaidPrice'];
                $goodsPrice = $val['goodsNums'] * $val['goodsPrice'];
                if ($profitType == 1) {
                    $goodsPriceNum += $val['goodsNums'] * $val['goodsCostPrice'];
                } else {
                    $goodsPriceNum += $val['goodsPaidPrice'];
                }
                $refundFee += $val['refundFee'];
                if ($type == 1) {
                    $goodsName = $val['goodsName'];
                    $goodsSn = $val['goodsSn'];
                    $goodsDesc = $val['goodsSpec'];
                    $goodsCatName1 = $val['goodsCatName1'];
                    $goodsCatName2 = $val['goodsCatName2'];
                    $orderNum += 1;
                }
                $goodsCatId3 = $val['goodsCatId3'];
                $goodsId = $val['goodsId'];
                //获取销售毛利列表
                if ($profitType == 1) {
                    $goodsName = $val['goodsName'];
                    $goodsSn = $val['goodsSn'];
                    $goodsDesc = $val['goodsSpec'];
                    $goodsCatName1 = $val['goodsCatName1'];
                    $goodsCatName2 = $val['goodsCatName2'];
                    if ($type == 2) {
                        $orderNum += 1;
                    }
                    $goodsCost = $val['goodsCostPrice'] * $goodsNum;//销售商品成本价
                    $goodsCostPrice += $goodsCost;
                    if ($val['is_return_goods'] == 1) {
                        $refundGoodsCostPrice += $val['goodsCostPrice'] * $refundGoodsCount;//退货成本
                    }
                    $profitPrice = $goodsPrice - $goodsCost;//毛利
                    $profit += $profitPrice;
                }
            }
//            $salesOrderMoney += $reportInfo['salesOrderMoney'];//销售订单总金额
            $salesOrderMoney += $goodsPaidPrice;//销售订单总金额
            $salesRefundOrderMoney += $refundFee;//退货金额
            $refundGoodsCostPriceSum += $refundGoodsCostPrice;//退货总成本
            $profitPercent = round($profit / $goodsPaidPrice * 100) . "％";//毛利率
            $profitSum += $profit;//所有毛利
            $goodsCostPriceSum += $goodsCostPrice;//所有商品成本价
            $practicalSum += $goodsPriceNum;//所有商品的实际价格
            $arr['goodsId'] = $goodsId;
            $arr['goodsCatId3'] = $goodsCatId3;
            $arr['goodsCatName3'] = $goodsCatName3;
            $arr['goodsPaidPrice'] = formatAmountNum($goodsPaidPrice);//商品实付金额
            $arr['refundFee'] = formatAmountNum($refundFee);//退款金额
            $arr['goodsPriceNum'] = formatAmountNum($goodsPriceNum);//商品实际金额
            $arr['rank'] = $rank;//综合排名
            $arr['diff_money_signle'] = formatAmount($diff_money_signle);
            if ($type == 1) {
                $marketSum += $marketNum;//所有销售数量
                $refundGoodsSum += $refundGoodsCount;//所有退款商品数量
                $refundOrderSum += $refundOrderCount;//所有退款订单数量
                $orderSum += $orderNum;//所有订单数量
                $arr['goodsName'] = $goodsName;//商品名称
                $arr['goodsSn'] = $goodsSn;
                $arr['goodsSpec'] = $goodsDesc;
                $arr['goodsCatName1'] = $goodsCatName1;
                $arr['goodsCatName2'] = $goodsCatName2;
                $arr['orderNum'] = $orderNum;//订单数量
                $arr['marketNum'] = $marketNum;//销售数量
                $arr['goodsNums'] = $goodsNums;//商品数量
                $arr['refundOrderCount'] = $refundOrderCount;//退款订单数量
                $arr['refundGoodsCount'] = $refundGoodsCount;//退款商品数量
            }
            //获取销售毛利列表
            if ($profitType == 1) {
//                $marketSum += $marketNum;//所有销售数量
//                $refundGoodsSum += $refundGoodsCount;//所有退款商品数量
//                $refundOrderSum += $refundOrderCount;//所有退款订单数量
//                $orderSum += $orderNum;//所有订单数量
                $arr['goodsName'] = $goodsName;
                $arr['goodsSn'] = $goodsSn;
                $arr['goodsSpec'] = $goodsDesc;
                $arr['goodsCatName1'] = $goodsCatName1;
                $arr['goodsCatName2'] = $goodsCatName2;
                $arr['orderNum'] = $orderNum;//订单数量
                $arr['goodsNums'] = $goodsNums;//商品数量
                $arr['marketNum'] = $marketNum;//销售数量
                $arr['refundOrderCount'] = $refundOrderCount;//退款订单数量
                $arr['refundGoodsCount'] = $refundGoodsCount;//退款商品数量
                $arr['refundGoodsCostPrice'] = formatAmountNum($refundGoodsCostPrice);//退货成本
                $arr['practicalPrice'] = bc_math($arr['goodsPaidPrice'], $arr['refundFee'], 'bcsub', 2);//实际金额=销售金额-退货金额
                $arr['practicalCostPrice'] = bc_math($arr['goodsPriceNum'], $arr['refundGoodsCostPrice'], 'bcsub', 2);//实际成本=销售成本-退货成本
                //$arr['profitSum'] = formatAmountNum($profit);//毛利
                $arr['profitSum'] = bc_math($arr['practicalPrice'], $arr['practicalCostPrice'], 'bcsub', 2);//毛利=实际金额-实际成本
                $arr['profitPercent'] = (bc_math($arr['profitSum'], $arr['practicalPrice'], 'bcdiv', 4) * 100) . '%';//毛利率=毛利/实际金额x100%
                $arr['goodsPriceNum'] = bc_math($val['goodsCostPrice'], $arr['goodsNums'], 'bcmul', 2);
            }
            $array[] = $arr;
        }
        //分页
        $pager = arrayPage($array, $page, $pageSize);
        $list = [];
        $list['list'] = $pager;
        //总合计
        $reportDate = [];
//        $reportDate['salesOrderMoney'] = formatAmountNum($salesOrderMoney);//销售订单总金额----该数据有点问题需要确定
        $reportDate['salesOrderMoney'] = formatAmountNum($practicalSum);//销售订单总金额
        $reportDate['salesRefundOrderMoney'] = formatAmountNum($salesRefundOrderMoney);//退货金额
        $reportDate['practicalSum'] = bc_math($practicalSum, $salesRefundOrderMoney, 'bcsub', 2);
        if ($type == 1) {
            $reportDate['orderSum'] = count($count_order_id_arr);//订单数量
            $reportDate['salesOrderNum'] = $marketSum;//销售数量
            $reportDate['salesRefundOrderNum'] = $refundOrderSum;//订单退款数量
            $reportDate['refundGoodsSum'] = $refundGoodsSum;//所有退款商品数量
        }
        //获取销售毛利列表
        if ($profitType == 1) {
            $orders_tab = M('orders');
            $order_id_arr = array_unique(array_column($data, 'orderId'));
            $order_list = $orders_tab->where(array(
                'orderId' => array('IN', $order_id_arr)
            ))->select();
            $realTotalMoney = array_sum(array_column($order_list, 'realTotalMoney'));
            $reportDate['salesOrderMoney'] = formatAmountNum($realTotalMoney);
            $reportDate['salesRefundOrderMoney'] = 0;
            foreach ($data as $item) {
                if ($item['is_return_goods'] == 1) {
                    $reportDate['salesRefundOrderMoney'] += $item['refundFee'];
                    if ($item['is_cancel_order'] == 1) {
                        foreach ($order_list as $order_val) {
                            if ($item['orderId'] == $order_val['orderId']) {
                                $reportDate['salesRefundOrderMoney'] += $order_val['deliverMoney'];
                            }
                        }
                    }
                }
            }
            $reportDate['salesRefundOrderMoney'] = formatAmountNum($reportDate['salesRefundOrderMoney']);
            $practicalSum = bc_math($reportDate['salesOrderMoney'], $reportDate['salesRefundOrderMoney'], 'bcsub', '2');
            $reportDate['practicalSum'] = $practicalSum;//实际金额
            $reportDate['orderSum'] = count($count_order_id_arr);//订单数量
            $reportDate['salesOrderNum'] = $marketSum;//销售数量
            $reportDate['salesRefundOrderNum'] = $refundOrderSum;//订单退款数量
            $reportDate['refundGoodsSum'] = $refundGoodsSum;//所有退款商品数量
            $reportDate['refundGoodsCostPriceSum'] = formatAmountNum($refundGoodsCostPriceSum);//退货总成本
            $reportDate['goodsCostPriceSum'] = formatAmountNum($goodsCostPriceSum);//销售成本
            $reportDate['practicalCostPriceSum'] = bc_math($reportDate['goodsCostPriceSum'], $reportDate['refundGoodsCostPriceSum'], 'bcsub', 2);//实际成本
            $reportDate['profitSum'] = bc_math($reportDate['practicalSum'], $reportDate['practicalCostPriceSum'], 'bcsub', 2);//毛利=实际金额-实际成本
        }
        $reportDate['diff_money_total'] = formatAmount($diff_money_total);
        //导出数据
        if ($export == 1) {
            $this->exportGoods($array, $reportDate, $type, $profitType);
            exit;
        }
        $list['sum'] = $reportDate;
        return $list;
    }


    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @param $type 1:按商品统计|2：按分类统计
     * @param $profitType
     * @return array
     * @throws PHPExcel_Exception
     * 获取商品销售列表
     * $profitType 销售毛利状态
     */
    public function commoditySaleListTwo($startDate, $endDate, $type, $profitType = 0, $shopId)
    {
        //复制上面的源码进行修改,主逻辑不变,现在客户要求数据结果和订单统计一致，mmp，临时修改下看看能够达到客户要求
        $page = (int)I('page', 1);
        $page_size = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//1:导出
        $orders_tab = M('orders');
        $order_goods_tab = M('order_goods');
        $pay_start_time = $startDate . ' 00:00:00';
        $pay_end_time = $endDate . ' 23:59:59';
        $order_list = $orders_tab->where(array(
            'shopId' => $shopId,
            'pay_time' => array('between', array($pay_start_time, $pay_end_time)),
            'isPay' => 1
        ))->select();
        foreach ($order_list as &$item) {
            $item['pay_time'] = date('Y-m-d', strtotime($item['pay_time']));
        }
        unset($item);
        $order_id_arr = array_column($order_list, 'orderId');
        $order_goods_where = [
            'orderId' => ['in', $order_id_arr]
        ];
        $order_goods_list = $order_goods_tab->where($order_goods_where)->select();
        $goods_id_arr_merge = array_column($order_goods_list, 'goodsId');//所有商品ID集合
        $sku_id_arr_merge = array_column($order_goods_list, 'skuId');//所欲SKU商品ID集合
        $order_count = count($order_id_arr);//订单总量
        $sales_goods_num = 0;//销售商品数量
        $sales_goods_amount = 0;//销售商品金额
        $diff_money_total = 0;//补差价金额
        $refund_goods_amount = 0;//退货金额
        $practical_amount = 0;//实际金额
        $order_goods_list_map = [];
        foreach ($order_goods_list as $order_goods_list_row) {
            $curr_order_goods_price = (float)bc_math($order_goods_list_row['goodsPrice'], $order_goods_list_row['goodsNums'], 'bcmul', 2);
            $sales_goods_amount = (float)bc_math($sales_goods_amount, $curr_order_goods_price, 'bcadd', 2);
            $sales_goods_num = (float)bc_math($sales_goods_num, $order_goods_list_row['goodsNums'], 'bcadd', 3);
            $unique_key = "{$order_goods_list_row['goodsId']}@{$order_goods_list_row['skuId']}";
            if (empty($order_goods_list_map[$unique_key])) {
                $order_goods_list_row['total_goods_price'] = $curr_order_goods_price;
            } else {
                $exist_data = $order_goods_list_map[$unique_key];
                $order_goods_list_row['goodsNums'] = (float)bc_math($exist_data['goodsNums'], $order_goods_list_row['goodsNums'], 'bcadd', 2);
                $order_goods_list_row['total_goods_price'] = (float)bc_math($curr_order_goods_price, $exist_data['total_goods_price'], 'bcadd', 2);
            }
            $order_goods_list_map[$unique_key] = $order_goods_list_row;
        }
        $mod_order_complainsrecord = M('order_complainsrecord');//售后退款记录表
        $order_complainsrecord_list = $mod_order_complainsrecord->where(array(
            'addTime' => array('between', array($pay_start_time, $pay_end_time)),
            'complainId' => array('GT', 0),
            //'orderId' => array('in', $order_id_arr)
        ))->select();//售后退款记录money

        $complain_id_arr = array_column($order_complainsrecord_list, 'complainId');
        $complain_id_arr = array_unique($complain_id_arr);
        $order_complains_tab = M('order_complains');
        $complains_where = [
            'complainId' => ['in', $complain_id_arr],
        ];
        $order_complains_list = $order_complains_tab->where($complains_where)->select();
        $order_complains_list_map = [];
        foreach ($order_complains_list as $order_complains_row) {
            $goodsId = $order_complains_row['goodsId'];
            $skuId = $order_complains_row['skuId'];
            $unique_key = "{$goodsId}@{$skuId}";
            $exist_data = $order_complains_list_map[$unique_key];
            if (empty($exist_data)) {
                $order_complains_list_map[$unique_key] = [
                    'returnAmount' => $order_complains_row['returnAmount'],
                    'returnNum' => $order_complains_row['returnNum'],
                ];
            } else {
                $order_complains_list_map[$unique_key]['returnAmount'] = (float)bc_math($order_complains_list_map[$unique_key]['returnAmount'], $order_complains_row['returnAmount'], 'bcadd', 2);
                $order_complains_list_map[$unique_key]['returnNum'] = (float)bc_math($order_complains_list_map[$unique_key]['returnNum'], $order_complains_row['returnNum'], 'bcadd', 3);
            }
        }
        if (!empty($order_complainsrecord_list)) {
            $refund_goods_amount_arr = array_column($order_complainsrecord_list, 'money');
            $refund_goods_amount = array_sum($refund_goods_amount_arr);
            $complainsrecord_goods_id_arr = array_column($order_complainsrecord_list, 'goodsId');
            $goods_id_arr_merge = array_merge($goods_id_arr_merge, $complainsrecord_goods_id_arr);
            $complainsrecord_sku_id_arr = array_column($order_complainsrecord_list, 'skuId');
            $sku_id_arr_merge = array_merge($sku_id_arr_merge, $complainsrecord_sku_id_arr);
        }
        $goods_pricediffe_tab = M('goods_pricediffe');
        $price_diff_list = $goods_pricediffe_tab->where(array(
            'payTime' => array('between', array($pay_start_time, $pay_end_time)),
            'isPay' => 1,
        ))->select();//商品差价补款
        $price_diff_list_map = [];
        foreach ($price_diff_list as $price_diff_list_row) {
            $goodsId = $price_diff_list_row['goodsId'];
            $skuId = $price_diff_list_row['skuId'];
            $unique_key = "{$goodsId}@{$skuId}";
            $exits_data = $price_diff_list_map[$unique_key];
            if (empty($exits_data)) {
                $price_diff_list_map[$unique_key] = [
                    'money' => (float)$price_diff_list_row['money']
                ];
            } else {
                $price_diff_list_map[$unique_key]['money'] = bc_math($exits_data['money'], $price_diff_list_row['money'], 'bcadd', 2);
            }
        }
        if (!empty($price_diff_list)) {
            $diff_price_arr = array_column($price_diff_list, 'money');
            $diff_money_total = array_sum($diff_price_arr);
            $diff_goods_id_arr = array_column($price_diff_list, 'goodsId');
            $goods_id_arr_merge = array_merge($goods_id_arr_merge, $diff_goods_id_arr);
            $diff_sku_id_arr = array_column($price_diff_list, 'skuId');
            $sku_id_arr_merge = array_merge($sku_id_arr_merge, $diff_sku_id_arr);
        }
        $goods_id_arr_merge = array_unique($goods_id_arr_merge);
        $goods_tab = M('goods');
        $goods_where = [
            'goodsId' => ['in', $goods_id_arr_merge]
        ];
        $goods_list = $goods_tab->where($goods_where)->field('goodsId,goodsName,goodsCatId1,goodsCatId2,goodsCatId3,goodsSn,goodsSpec')->select();
        $goods_list_map = [];
        foreach ($goods_list as $key => $goods_row) {
            $goods_row['skuId'] = 0;
            $goods_list[$key] = $goods_row;
            $goods_list_map[$goods_row['goodsId']] = $goods_row;
        }
        $sku_system_tab = M('sku_goods_system');
        $sku_where = [
            'skuId' => ['in', $sku_id_arr_merge]
        ];
        $sku_base_list = $sku_system_tab->where($sku_where)->select();
        $cat_id_1_arr = array_column($goods_list, 'goodsCatId1');
        $cat_id_2_arr = array_column($goods_list, 'goodsCatId2');
        $cat_id_3_arr = array_column($goods_list, 'goodsCatId3');
        $cat_id_arr_merge = array_merge($cat_id_1_arr, $cat_id_2_arr, $cat_id_3_arr);
        $cat_id_arr_merge = array_unique($cat_id_arr_merge);
        $all_data_list = [];
        $goods_cat_tab = M('goods_cats');
        $goods_cat_where = [
            'catId' => ['in', $cat_id_arr_merge]
        ];
        $goods_cat_list = $goods_cat_tab->where($goods_cat_where)->select();
        $goods_cat_list_map = [];
        foreach ($goods_cat_list as $goods_cat_row) {
            $goods_cat_list_map[$goods_cat_row['catId']] = $goods_cat_row['catName'];
        }
        $all_goods_list = $goods_list;//所有的商品
        foreach ($sku_base_list as $sku_row) {
            $curr_goods_row = $goods_list_map[$sku_row['goodsId']];
            if (empty($curr_goods_row)) {
                continue;
            }
            $curr_goods_row['goodsSn'] = $sku_row['skuBarcode'];
            $curr_goods_row['skuId'] = $sku_row['skuId'];
            $all_goods_list[] = $curr_goods_row;
        }
        $total_return_goods_num = (float)0; //已售后商品数量总计
        $keywords = I('keywords');
        $goodsCatId1 = (int)I('goodsCatId1');
        $goodsCatId2 = (int)I('goodsCatId2');
        $goodsCatId3 = (int)I('goodsCatId3');
        foreach ($all_goods_list as $goods_row) {
            if (!empty(I('keywords'))) {
                if (strpos($goods_row['goodsName'], $keywords) === false && strpos($goods_row['goodsSn'], $keywords) === false) {
                    continue;
                }
            }
            if ($goodsCatId1 != 0 && $goodsCatId1 != $goods_row['goodsCatId1']) {
                continue;
            }
            if ($goodsCatId2 != 0 && $goodsCatId2 != $goods_row['goodsCatId2']) {
                continue;
            }
            if ($goodsCatId3 != 0 && $goodsCatId3 != $goods_row['goodsCatId3']) {
                continue;
            }
            $goods_row['goodsCatName1'] = $goods_cat_list_map[$goods_row['goodsCatId1']];
            $goods_row['goodsCatName2'] = $goods_cat_list_map[$goods_row['goodsCatId2']];
            $goods_row['goodsCatName3'] = $goods_cat_list_map[$goods_row['goodsCatId3']];
            $goods_row['orderNum'] = 0; //订单数量
            $goods_row['marketNum'] = (float)0;//销售数量
            $goods_row['goodsPaidPrice'] = (float)0;//销售金额
            $goods_row['diff_money_signle'] = (float)0;//补差价金额
            $goods_row['refundOrderCount'] = 0;//退单数量 PS：这里只针对退货单
            $goods_row['refundGoodsCount'] = (float)0;//退货数量
            $goods_row['refundFee'] = (float)0;//退货金额
            $goodsId = $goods_row['goodsId'];
            $skuId = $goods_row['skuId'];
            $unique_key = "{$goodsId}@{$skuId}";
            $order_goods_list_map_row = $order_goods_list_map[$unique_key];
            if (!empty($order_goods_list_map_row)) {
                $goods_row['orderNum'] += 1;
                $goods_row['marketNum'] = (float)bc_math($goods_row['marketNum'], $order_goods_list_map_row['goodsNums'], 'bcadd', 3);
                $goods_row['goodsPaidPrice'] = (float)bc_math($goods_row['goodsPaidPrice'], $order_goods_list_map_row['total_goods_price'], 'bcadd', 2);
            }
            $order_complains_list_row = $order_complains_list_map[$unique_key];
            if (!empty($order_complains_list_row)) {
                $goods_row['refundGoodsCount'] = (float)bc_math($goods_row['refundGoodsCount'], $order_complains_list_row['returnNum'], 'bcadd', 3);
                $goods_row['refundFee'] = (float)bc_math($goods_row['refundFee'], $order_complains_list_row['returnAmount'], 'bcadd', 2);
                $total_return_goods_num = (float)bc_math($total_return_goods_num, $order_complains_list_row['returnNum'], 'bcadd', 3);
            }
            $price_diff_list_map_row = $price_diff_list_map[$unique_key];
            if (!empty($price_diff_list_map_row)) {
                $goods_row['diff_money_signle'] = (float)bc_math($goods_row['diff_money_signle'], $price_diff_list_map_row['money'], 'bcadd', 2);
            }
            if ($goods_row['orderNum'] == 0 && $goods_row['refundOrderCount'] == 0 && $goods_row['refundGoodsCount'] == 0 && $goods_row['diff_money_signle'] == 0) {
                continue;
            }
            $all_data_list[] = $goods_row;
        }
        $refund_amount_total = (float)bc_math($refund_goods_amount, $diff_money_total, 'bcadd', 2);
        $practical_amount = (float)bc_math($sales_goods_amount, $refund_amount_total, 'bcsub', 2);
        //分页
        $page_list = arrayPage($all_data_list, $page, $page_size);
        $list['list'] = $page_list;
        //总合计
        $sum = [//返回字段继续沿用之前已存在的字段
            'orderSum' => $order_count,//订单总量
            'salesOrderNum' => $sales_goods_num,//销售数量
            'salesOrderMoney' => $sales_goods_amount,//销售金额
            'diff_money_total' => $diff_money_total,//补差金额
            'salesRefundOrderMoney' => $refund_goods_amount,//退货金额
            'practicalSum' => $practical_amount,//实际金额
        ];
        if ($type == 1) {
            $sum['salesRefundOrderNum'] = count($complain_id_arr);//退货单数量
            $sum['refundGoodsSum'] = $total_return_goods_num;//所有退款商品数量
        }
        //获取销售毛利列表
//        if ($profitType == 1) {
//            $orders_tab = M('orders');
//            $order_id_arr = array_unique(array_column($data, 'orderId'));
//            $order_list = $orders_tab->where(array(
//                'orderId' => array('IN', $order_id_arr)
//            ))->select();
//            $realTotalMoney = array_sum(array_column($order_list, 'realTotalMoney'));
//            $reportDate['salesOrderMoney'] = formatAmountNum($realTotalMoney);
//            $reportDate['salesRefundOrderMoney'] = 0;
//            foreach ($data as $item) {
//                if ($item['is_return_goods'] == 1) {
//                    $reportDate['salesRefundOrderMoney'] += $item['refundFee'];
//                    if ($item['is_cancel_order'] == 1) {
//                        foreach ($order_list as $order_val) {
//                            if ($item['orderId'] == $order_val['orderId']) {
//                                $reportDate['salesRefundOrderMoney'] += $order_val['deliverMoney'];
//                            }
//                        }
//                    }
//                }
//            }
//            $reportDate['salesRefundOrderMoney'] = formatAmountNum($reportDate['salesRefundOrderMoney']);
//            $practicalSum = bc_math($reportDate['salesOrderMoney'], $reportDate['salesRefundOrderMoney'], 'bcsub', '2');
//            $reportDate['practicalSum'] = $practicalSum;//实际金额
//            $reportDate['orderSum'] = count($count_order_id_arr);//订单数量
//            $reportDate['salesOrderNum'] = $marketSum;//销售数量
//            $reportDate['salesRefundOrderNum'] = $refundOrderSum;//订单退款数量
//            $reportDate['refundGoodsSum'] = $refundGoodsSum;//所有退款商品数量
//            $reportDate['refundGoodsCostPriceSum'] = formatAmountNum($refundGoodsCostPriceSum);//退货总成本
//            $reportDate['goodsCostPriceSum'] = formatAmountNum($goodsCostPriceSum);//销售成本
//            $reportDate['practicalCostPriceSum'] = bc_math($reportDate['goodsCostPriceSum'], $reportDate['refundGoodsCostPriceSum'], 'bcsub', 2);//实际成本
//            $reportDate['profitSum'] = bc_math($reportDate['practicalSum'], $reportDate['practicalCostPriceSum'], 'bcsub', 2);//毛利=实际金额-实际成本
//        }
        $sum['diff_money_total'] = (float)$diff_money_total;
        //导出数据
        if ($export == 1) {
            $this->exportGoods($all_goods_list, $sum, $type, $profitType);
            exit;
        }
        $list['sum'] = $sum;
        return $list;
    }

    /**
     * @param $list
     * @param $reportDate
     * @param $type
     * @param $profitType
     * @throws \PHPExcel_Exception
     * 导出数据操作----获取商品销售列表
     */
    public function exportGoods($list, $reportDate, $type, $profitType)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '商品销量';
        $excel_filename = '商品销量_' . date('Ymd_His');
        if ($profitType == 1) {
            $title = '毛利列表';
            $excel_filename = '毛利列表_' . date('Ymd_His');
        }
        if ($type == 1 && $profitType != 1) {//获取商品销售列表----按商品统计
            $sheet_title = array('商品编号', '商品名称', '分类', '描述', '订单数量', '销售数量', '销售金额', '退单数量', '退货数量', '退货金额', '补差价金额', '实际金额', '综合排名');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');//12
        } else if ($type == 2 && $profitType != 1) {//获取商品销售列表----按商品分类统计
            $sheet_title = array('分类', '销售金额', '退货金额', '实际金额', '补差价金额', '综合排名');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F');
        } else if ($type == 1 && $profitType == 1) {//获取销售毛利列表----按商品统计
            $sheet_title = array('商品名称', '分类', '订单数量', '销售数量', '销售金额', '退货数量', '退货金额', '实际金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        } else if ($type == 2 && $profitType == 1) {//获取销售毛利列表----按商品分类统计
            $sheet_title = array('分类', '订单数量', '销售数量', '销售金额', '退货数量', '退货金额', '实际金额', '实际成本', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        }

        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            if ($i == count($list)) {
                $temp = $reportDate;
                if (!empty($reportDate)) {
                    $temp = [];
                    if ($type == 1 && $profitType != 1) {
                        $temp['goodsSn'] = "合计";
                        $temp['orderNum'] = $reportDate['orderSum'];//订单数量
                        $temp['marketNum'] = $reportDate['salesOrderNum'];//销售数量
//                        $temp['goodsPaidPrice'] = $reportDate['salesOrderMoney'];//销售金额
                        $temp['goodsPaidPrice'] = $reportDate['practicalSum'];//销售金额
                        $temp['refundOrderCount'] = $reportDate['salesRefundOrderNum'];//退单数量
                        $temp['refundGoodsCount'] = $reportDate['refundGoodsSum'];//退货数量
                        $temp['refundFee'] = $reportDate['salesRefundOrderMoney'];//退货金额
                        $temp['diff_money_signle'] = $reportDate['diff_money_total'];//补差价金额
                        $temp['goodsPriceNum'] = $reportDate['practicalSum'];//实际金额
                    } else if ($type == 2 && $profitType != 1) {
                        $temp['goodsCatName3'] = "合计";
                        $temp['goodsPaidPrice'] = $reportDate['salesOrderMoney'];//销售金额
                        $temp['refundFee'] = $reportDate['salesRefundOrderMoney'];//退货金额
                        $temp['goodsPriceNum'] = $reportDate['practicalSum'];//实际金额
                        $temp['diff_money_signle'] = $reportDate['diff_money_total'];//补差价金额
                    } else if ($type == 1 && $profitType == 1) {
                        $temp['goodsName'] = "合计";
                        $temp['orderNum'] = $reportDate['orderSum'];//订单数量
                        $temp['marketNum'] = $reportDate['salesOrderNum'];//销售数量
                        $temp['refundFee'] = $reportDate['salesRefundOrderMoney'];//退货金额
                        $temp['goodsPriceNum'] = $reportDate['practicalSum'];//所有商品的实际价格
                        $temp['refundOrderCount'] = $reportDate['salesRefundOrderNum'];//订单退款数量
                        $temp['profitSum'] = $reportDate['profitSum'];//所有毛利
                    } else if ($type == 2 && $profitType == 1) {
                        $temp['goodsCatName3'] = "合计";
                        $temp['orderNum'] = $reportDate['orderSum'];//订单数量
                        $temp['marketNum'] = $reportDate['practicalSum'];//销售数量
                        $temp['goodsPriceNum'] = $reportDate['salesOrderMoneySum'];//销售金额
                        $temp['goodsPaidPrice'] = $reportDate['goodsCostPriceSum'];//销售成本
                        $temp['refundGoodsCount'] = $reportDate['refundGoodsSum'];//退货数量
                        $temp['refundFee'] = $reportDate['practicalSum'];//实际金额
                        $temp['profitSum'] = $reportDate['profitSum'];//毛利
                    }
                }
            }
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                if ($type == 1 && $profitType != 1) {
                    switch ($j) {
                        case 0 :
                            //商品编号
                            $cellvalue = $temp['goodsSn'];
                            break;
                        case 1 :
                            //商品名称
                            $cellvalue = $temp['goodsName'];
                            break;
                        case 2 :
                            //分类
                            $cellvalue = "";
                            if (!empty($temp['goodsCatName1'])) {
                                $cellvalue = $temp['goodsCatName1'] . '/' . $temp['goodsCatName2'] . '/' . $temp['goodsCatName3'];
                            }
                            break;
                        case 3 :
                            //描述
                            $cellvalue = $temp['goodsSpec'];
                            break;
                        case 4 :
                            //订单数量
                            $cellvalue = $temp['orderNum'];
                            break;
                        case 5 :
                            //销售数量
                            $cellvalue = $temp['marketNum'];
                            break;
                        case 6 :
                            //销售金额
                            $cellvalue = $temp['goodsPaidPrice'];
                            break;
                        case 7 :
                            //退单数量
                            $cellvalue = $temp['refundOrderCount'];
                            break;
                        case 8 :
                            //退货数量
                            $cellvalue = $temp['refundGoodsCount'];
                            break;
                        case 9 :
                            //退货金额
                            $cellvalue = $temp['refundFee'];
                            break;
                        case 10 :
                            //补差价金额
                            $cellvalue = $temp['diff_money_signle'];
                            break;
                        case 11 :
                            //实际金额
                            $cellvalue = $temp['goodsPriceNum'];
                            break;
                        case 12 :
                            //综合排名
                            $cellvalue = $temp['rank'];
                            break;
                    }
                } else if ($type == 2 && $profitType != 1) {
                    switch ($j) {
                        case 0 :
                            //分类
                            $cellvalue = $temp['goodsCatName3'];
                            break;
                        case 1 :
                            //销售金额
                            $cellvalue = $temp['goodsPaidPrice'];
                            break;
                        case 2 :
                            //退货金额
                            $cellvalue = $temp['refundFee'];
                            break;
                        case 3 :
                            //实际金额
                            $cellvalue = $temp['goodsPriceNum'];
                            break;
                        case 4 :
                            //补差价金额
                            $cellvalue = $temp['diff_money_signle'];
                            break;
                        case 5 :
                            //综合排名
                            $cellvalue = $temp['rank'];
                            break;
                    }
                } else if ($type == 1 && $profitType == 1) {
                    switch ($j) {
                        case 0 :
                            //商品名称
                            $cellvalue = $temp['goodsName'];
                            break;
                        case 1 :
                            //分类
                            $cellvalue = '';
                            if (!empty($temp['goodsCatName1'])) {
                                $cellvalue = $temp['goodsCatName1'] . '/' . $temp['goodsCatName2'] . '/' . $temp['goodsCatName3'];
                            }
                            break;
                        case 2 :
                            //订单数量
                            $cellvalue = $temp['orderNum'];
                            break;
                        case 3 :
                            //销售数量
                            $cellvalue = $temp['marketNum'];
                            break;
                        case 4 :
                            //销售金额
                            $cellvalue = $temp['goodsPriceNum'];
                            break;
                        case 5 :
                            //退货数量
                            $cellvalue = $temp['refundOrderCount'];
                            break;
                        case 6 :
                            //退货金额
                            $cellvalue = $temp['refundFee'];
                            break;
                        case 7 :
                            //实际金额
                            $cellvalue = $temp['goodsPriceNum'];
                            break;
                        case 8 :
                            //毛利
                            $cellvalue = $temp['profitSum'];
                            break;
                        case 9 :
                            //毛利率
                            $cellvalue = $temp['profitPercent'];
                            break;
                    }
                } else if ($type == 2 && $profitType == 1) {
                    switch ($j) {
                        case 0 :
                            //分类
                            $cellvalue = $temp['goodsCatName3'];
                            break;
                        case 1 :
                            //订单数量
                            $cellvalue = $temp['orderNum'];
                            break;
                        case 2 :
                            //销售数量
                            $cellvalue = $temp['marketNum'];
                            break;
                        case 3 :
                            //销售金额
                            $cellvalue = $temp['goodsPriceNum'];
                            break;
                        case 4 :
                            //退货数量
                            $cellvalue = $temp['refundGoodsCount'];
                            break;
                        case 5 :
                            //实际金额
                            $cellvalue = $temp['refundFee'];
                            break;
                        case 6 :
                            //实际成本
                            $cellvalue = $temp['goodsPaidPrice'];
                            break;
                        case 7 :
                            //毛利
                            $cellvalue = $temp['profitSum'];
                            break;
                        case 8 :
                            //毛利率
                            $cellvalue = $temp['profitPercent'];
                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $goodsId
     * @param $goodsCatId3
     * @param $shopId
     * @param int $profitType
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 商品销售----获取用户商品销量明细---根据商品ID获取
     * $profitType 销售毛利状态
     */
    public function commoditySalesUserDetail($goodsId, $goodsCatId3, $profitType = 0, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $type = (int)I('statType', 1);//1:按商品统计|2：按分类统计
        $export = (int)I('export', 0);//0:导出
        $where = [];
        if ($type == 1) {
            $where['sor.goodsId'] = $goodsId;
        } else {
            $where['sor.goodsCatId3'] = $goodsCatId3;
        }
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        $where['sor.shopId'] = $shopId;
        $field = "wu.userName,sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3";
        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

//        $data = M('sales_order_report sor')
//            ->join('left join wst_goods wg ON wg.goodsId = sor.goodsId')
//            ->join('left join wst_users wu ON wu.userId = sor.userId')
//            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = sor.goodsCatId1')
//            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = sor.goodsCatId2')
//            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = sor.goodsCatId3')
//            ->where($where)
//            ->field($field)
//            ->select();
        foreach ($data as $v) {
            if ($type == 1) {
                $res[$v['userId']][] = $v;
            } else {
                $res[$v['goodsCatId3']][] = $v;
            }
        }
        $arr = [];
        $array = [];
        $goodsNumSum = 0;//销售数量总数
        $goodsCostPriceSum = 0;//销售成本价总价
        $goodsPaidPriceSum = 0;//销售实付金额总价
        $refundGoodsCountSum = 0;//退款商品数量总数
        $refundCostPriceSum = 0;//退款成本价总价
        $refundFeeSum = 0;//退款金额总价
        $profitSum = 0;//毛利总和
        foreach ($res as $v) {
            $goodsNums = 0;//销售数量
            $goodsPaidPrice = 0;//商品实付金额
            $refundOrderCount = 0;//退款订单数量
            $refundGoodsCount = 0;//退款商品数量
            $refundFee = 0;//退款金额
            $refundCostPriceNum = 0;//退款成本价
            $goodsPriceNum = 0;//商品实际金额
            $goodsCostPriceNum = 0;//销售成本价
            $goodsCostPrice = 0;//进货价--成本价
            $profit = 0;//毛利
            $goodsCostPrices = 0;//当前商品成本价
            foreach ($v as $val) {
                $userName = $val['userName'];//客户名称
                $refundFee += $val['refundFee'];//退款金额
                $goodsPaidPrice += $val['goodsPaidPrice'];//商品实付金额
                $goodsNum = $val['goodsNums'];
                $goodsNums += $goodsNum;
                $goodsPrice = $val['goodsNums'] * $val['goodsPrice'];//商品实际金额
                $goodsPriceNum += $goodsPrice;
                if ($val['is_return_goods'] == 1) {//退款
                    $refundOrderCount += 1;
                    $refundGoodsCount += $val['goodsNums'];
                }
                if ($type == 1) {
                    //商品基本信息
                    $goodsName = $val['goodsName'];
                    $goodsSn = $val['goodsSn'];
                    $goodsDesc = $val['goodsSpec'];
                    $goodsCatName1 = $val['goodsCatName1'];
                    $goodsCatName2 = $val['goodsCatName2'];
                }
                $goodsCatName3 = $val['goodsCatName3'];
                //客户	退货数量	退货金额	退货成本	实际金额	实际成本	毛利	毛利率
                if ($profitType == 1) {
                    $goodsCostPrice = $val['goodsCostPrice'];//成本单价
                    $goodsCost = $goodsCostPrice * $goodsNum;//销售成本价
                    $goodsCostPriceNum += $goodsCost;
                    if ($val['is_return_goods'] == 1) {
                        $refundCostPriceNum += $goodsCostPrice * $refundGoodsCount;//退款成本价
                    }
                    $goodsCostPrices += $val['goodsCostPrice'] * $val['goodsNums'];//当前商品成本价
                    //$profitNum = $goodsPrice - $goodsCost;//毛利
                    //$profit += $profitNum;
                    $reportDate = $val['reportDate'];//时间
                }
            }
            $arr['userName'] = $userName;
            $arr['goodsPaidPrice'] = formatAmountNum($goodsPaidPrice);
            $arr['refundFee'] = formatAmountNum($refundFee);
            $arr['goodsPriceNum'] = formatAmountNum($goodsPriceNum);
            if ($type == 1) {
                $arr['goodsNums'] = $goodsNums;
                $arr['refundOrderCount'] = $refundOrderCount;
                $arr['refundGoodsCount'] = $refundGoodsCount;
            }
            if ($profitType == 1) {
                $goodsPaidPriceSum += formatAmountNum($goodsPaidPrice);//销售实付金额总价
                $goodsNumSum += $goodsNums;//销售数量总数
                $goodsCostPriceSum += $goodsCostPriceNum;//销售成本价总价
                $refundCostPriceSum += $refundCostPriceNum;//退款成本价总价
                $refundGoodsCountSum += $refundGoodsCount;//退款商品数量总数
                $refundFeeSum += $refundFee;//退款商品金额总价
                $profitSum += $profit;//毛利总和
                $profitPercent = round($profit / $goodsCostPriceNum * 100, 2) . "％";//毛利率
                $arr['goodsCostPrice'] = formatAmountNum($goodsCostPrice);//成本单价
                $arr['goodsCostPriceNum'] = formatAmountNum($goodsCostPriceNum);//销售成本价
                $arr['refundCostPriceNum'] = formatAmountNum($refundCostPriceNum);//退款成本价
                $practicalPrice = bc_math($arr['goodsPaidPrice'], $arr['refundFee'], 'bcsub', 2);//实际金额=销售金额-退货金额
                $goods_cos_price_num = bc_math($arr['goodsCostPriceNum'], $arr['refundCostPriceNum'], 'bcsub', 2);//实际成本价
                $profit = bc_math($practicalPrice, $goods_cos_price_num, 'bcsub', 2);//毛利=实际金额-实际成本
                $arr['profitPercent'] = (bc_math($profit, $practicalPrice, 'bcdiv', 4) * 100) . '%';//毛利率
                $arr['profit'] = formatAmountNum($profit);//毛利
                $arr['reportDate'] = $reportDate;//时间
            }
            $array[] = $arr;
        }
        $arrayPage = arrayPage($array, $page, $pageSize);
        $list = [];
        $list['list'] = $arrayPage;
        //商品基本信息
        $goodsData = [];
        if ($type == 1) {
            $goodsData['goodsName'] = $goodsName;
            $goodsData['goodsSn'] = $goodsSn;
            $goodsData['goodsDesc'] = $goodsDesc;
            $goodsData['goodsCatName1'] = $goodsCatName1;
            $goodsData['goodsCatName2'] = $goodsCatName2;
        }
        $goodsData['goodsCatName3'] = $goodsCatName3;
        if (empty($data)) {
            $goodsData = [];
        }
        $list['commodity'] = $goodsData;
        if ($profitType == 1) {
            $reportDate = [];
            $reportDate['goodsNumSum'] = $goodsNumSum;//销售数量总数
            $reportDate['goodsPaidPriceSum'] = formatAmountNum($goodsPaidPriceSum);//销售实付金额总价
            $reportDate['goodsCostPriceSum'] = formatAmountNum($goodsCostPriceSum);//销售成本价总价
            $reportDate['refundGoodsCountSum'] = $refundGoodsCountSum;//退货数量
            $reportDate['refundFeeSum'] = formatAmountNum($refundFeeSum);//退货金额
            $reportDate['refundCostPriceSum'] = formatAmountNum($refundCostPriceSum);//退款成本价总价
//            $reportDate['goodsPaidPriceSum'] = formatAmountNum($goodsPaidPriceSum);//销售实付金额总价
            $reportDate['profitSum'] = formatAmountNum($profitSum);//所有毛利
            $list['sum'] = $reportDate;
            if ($export == 1) {
                $this->exportCommoditySalesUserDetail($array, $goodsData, $reportDate);
                exit();
            }
            return $list;
        }
        return $list;
    }

    /**
     * @param $list
     * @param $goodsData
     * @param $reportDate
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出---【销售毛利列表--商品列表---获取商品详情】
     */
    public function exportCommoditySalesUserDetail($list, $goodsData, $reportDate)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = $goodsData['goodsName'];
        $excel_filename = '商品毛利信息_' . date('Ymd_His');
        $sheet_title = array('送货日期', '客户名称', '销售金额', '退款金额', '商品实际金额', '销售数量', '退款订单数量', '退款商品数量', '商品成本价', '销售成本', '退款成本价', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M');
        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            if ($i == count($list)) {
                $temp = $reportDate;
                if (!empty($reportDate)) {
                    $temp['reportDate'] = "合计";
                    $temp['goodsNums'] = $reportDate['goodsNumSum'];//销售数量总数
                    $temp['goodsPaidPrice'] = $reportDate['goodsPaidPriceSum'];//销售实付金额总价
                    $temp['goodsCostPriceNum'] = $reportDate['goodsCostPriceSum'];//销售成本价总价
                    $temp['refundGoodsCount'] = $reportDate['refundGoodsCountSum'];//退货数量
                    $temp['refundFee'] = $reportDate['refundFeeSum'];//退货金额
                    $temp['refundCostPriceNum'] = $reportDate['refundCostPriceSum'];//退款成本价总价
                    $temp['profit'] = $reportDate['profitSum'];//所有毛利
                }
            }
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                switch ($j) {
                    case 0 :
                        //送货日期
                        $cellvalue = $temp['reportDate'];
                        break;
                    case 1 :
                        //客户名称
                        $cellvalue = $temp['userName'];
                        break;
                    case 2 :
                        //销售金额
                        $cellvalue = $temp['goodsPaidPrice'];
                        break;
                    case 3 :
                        //退款金额
                        $cellvalue = $temp['refundFee'];
                        break;
                    case 4 :
                        //商品实际金额
                        $cellvalue = $temp['goodsPriceNum'];
                        break;
                    case 5 :
                        //销售数量
                        $cellvalue = $temp['goodsNums'];
                        break;
                    case 6 :
                        //退款订单数量
                        $cellvalue = $temp['refundOrderCount'];
                        break;
                    case 7 :
                        //退款商品数量
                        $cellvalue = $temp['refundGoodsCount'];
                        break;
                    case 8 :
                        //商品成本价
                        $cellvalue = $temp['goodsCostPrice'];
                        break;
                    case 9 :
                        //销售成本
                        $cellvalue = $temp['refundFee'];
                        break;
                    case 10 :
                        //退款成本价
                        $cellvalue = $temp['refundCostPriceNum'];
                        break;
                    case 11 :
                        //毛利
                        $cellvalue = $temp['profit'];
                        break;
                    case 12 :
                        //毛利率
                        $cellvalue = $temp['profitPercent'];
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
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @param $profitType
     * @return array
     * 商品销售---图表模式
     * $profitType 销售毛利状态
     */
    public function CommoditySaleData($startDate, $endDate, $profitType = 0, $shopId)
    {
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        $where['sor.shopId'] = $shopId;
        $field = "sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3";
        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

//        $data = M('sales_order_report sor')
//            ->join('left join wst_goods wg ON wg.goodsId = sor.goodsId')
//            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = sor.goodsCatId1')
//            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = sor.goodsCatId2')
//            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = sor.goodsCatId3')
//            ->where($where)
//            ->field($field)
//            ->select();
        foreach ($data as $v) {
            $res[$v['goodsId']][] = $v;
        }
        $arr = [];
        $array = [];
        $return = [];
        //$salesRefundOrderNum = 0;//订单退款数量
        $salesRefundOrderMoney = 0;//订单退款金额
        //$salesOrderNum = 0;//销售订单数量
        $salesOrderMoney = 0;//销售订单总金额
        $goodsCostPriceSum = 0;//成本价总金额
        $profitSum = 0;//毛利总金额
        $goodsNums = 0;//销售商品数量
        $refundFeeGoodsPriceSum = 0;//退款商品成本总价格
        $order_id_arr = array_unique(array_column($data, 'orderId'));
        $salesOrderNum = count($order_id_arr);
        $return_order_id = array();
        $cancel_order_id = array();
        foreach ($data as $item) {
            if ($item['is_return_goods'] == 1) {
                $return_order_id[] = $item['orderId'];
            }
            if ($item['is_cancel_order'] == 1) {
                $cancel_order_id[] = $item['orderId'];
            }
        }
        $orders_tab = M('orders');
        $order_list = $orders_tab->where(array(
            'orderId' => array('IN', $order_id_arr)
        ))->select();
        $salesRefundOrderNum = count(array_unique($return_order_id));
        $refundFeeNum = 0;
        foreach ($res as $v) {
            $goodsPaidPrice = 0;//商品实付金额
            $refundFee = 0;//退款金额
            $goodsCostPrice = 0;//成本价
            $goodsPriceNum = 0;//商品实际金额
            $profit = 0;//毛利
            foreach ($v as $val) {
                //$reportInfo = M('order_report sor')->where(['reportId' => $val['reportId']])->find();
                $goodsPaidPrice += $val['goodsPaidPrice'];
                $goodsNum = $val['goodsNums'];
                $goodsNums += $goodsNum;
                $goodsPrice = $val['goodsNums'] * $val['goodsPrice'];//销售商品价格
                if ($val['is_return_goods'] == 1) {//退款
                    $refundFee += $val['refundFee'];
                    $refundFeeNum += $val['refundFee'];
                    $refundFeeGoodsPrice = $val['goodsNums'] * $val['goodsCostPrice'];//退款商品成本价格
                    $refundFeeGoodsPriceSum += $refundFeeGoodsPrice;
                } else {
                    $goodsPriceNum += $goodsPrice;
                }
                $goodsName = $val['goodsName'];
                if ($profitType == 1) {
                    $goodsCost = $val['goodsCostPrice'] * $goodsNum;//销售商品成本价
                    $goodsCostPrice += $goodsCost;
                    $profitNum = $goodsPrice - $goodsCost;//毛利
                    $profit += $profitNum;
                }
                $salesRefundOrderMoney += $val['refundFee'];
                if ($val['is_return_goods'] != 1) {
                    $array[] = array(
                        'goodsName' => $goodsName,
                        'goodsPaidPrice' => $val['goodsPaidPrice'],
                        'percent' => '',
                    );
                } else {
                    $return[] = array(
                        'goodsName' => $goodsName,
                        'refundFee' => $val['refundFee'],
                        'percent' => '',
                    );
                }
            }
//            $salesRefundOrderNum += $reportInfo['salesRefundOrderNum'];//订单退款数量
//            $salesRefundOrderMoney += $reportInfo['salesRefundOrderMoney'];//订单退款金额
            //$salesOrderNum += $reportInfo['salesOrderNum'];//销售订单数量
//            $salesOrderMoney += $reportInfo['salesOrderMoney'];//销售订单总金额
            $salesOrderMoney += $goodsPriceNum;//销售订单总金额
//            $arr['goodsName'] = $goodsName;
//            $arr['goodsPaidPrice'] = formatAmountNum($goodsPaidPrice);//商品实付金额
//            $arr['percent'] = round($goodsPaidPrice / $goodsPaidPrice * 100, 1) . "％";
//            $returnList['goodsName'] = $goodsName;
//            $returnList['refundFee'] = formatAmountNum($refundFee);//退款金额
//            $returnList['percent'] = round($refundFee / $reportInfo['salesRefundOrderMoney'] * 100, 1) . "％";
            //$array[] = $arr;
            //$return[] = $returnList;
            if ($profitType == 1) {
                $goodsCostPriceSum += $goodsCostPrice;//成本价总金额
                $profitSum += $profit;//毛利总金额
            }
        }
        $list = [];
        $list['salesRefundOrderNum'] = $salesRefundOrderNum;//订单退款数量
        $list['salesRefundOrderMoney'] = formatAmountNum($salesRefundOrderMoney);//订单退款金额
        $list['salesOrderNum'] = $salesOrderNum;//销售订单数量
        $list['salesOrderMoney'] = formatAmountNum($salesOrderMoney);//销售订单总金额
        $arrayCount = array_slice($array, 0, 10);//数组取出指定个数
        foreach ($arrayCount as &$item) {
            $item['percent'] = (bc_math($item['goodsPaidPrice'], $salesOrderMoney, 'bcdiv', 2) * 100) . '%';
        }
        unset($item);
        foreach ($return as &$item) {
            $item['percent'] = (bc_math($item['refundFee'], $salesRefundOrderMoney, 'bcdiv', 2) * 100) . '%';
        }
        unset($item);
        $list['saleList'] = $arrayCount;
        $list['returnList'] = array_slice($return, 0, 10);
        if ($profitType == 1) {
            $salesRefundOrderMoney = $refundFeeNum;//退货金额
            if (!empty($cancel_order_id)) {
                foreach ($order_list as $item) {
                    if (in_array($item['orderId'], $cancel_order_id)) {
                        $salesRefundOrderMoney += $item['deliverMoney'];
                    }
                }
            }
            $list = [];
            $salesOrderMoney = formatAmountNum(array_sum(array_column($order_list, 'realTotalMoney')));//销售金额
            $list['practicalPrice'] = bc_math($salesOrderMoney, $salesRefundOrderMoney, 'bcsub', 2);//实际金额=销售金额-退货金额
            $list['goodsCostPriceSum'] = bc_math($goodsCostPriceSum, $refundFeeGoodsPriceSum, 'bcsub', 2);//实际成本
            $list['profitSum'] = bc_math($list['practicalPrice'], $list['goodsCostPriceSum'], 'bcsub', 2);//毛利=实际金额-实际成本
            $list['profitPercent'] = (bc_math($list['profitSum'], $list['practicalPrice'], 'bcdiv', 4) * 100) . '%';//毛利率=毛利/实际金额x100%
        }
        return $list;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 获取订单统计列表
     */
//    public function statOrder($startDate, $endDate, $shopId)
//    {
//        $page = (int)I('page', 1);
//        $pageSize = (int)I('pageSize', 15);
//        $export = (int)I('export', 0);//导出
//        $where = [];
//        $where['reportDate'] = ['between', [$startDate, $endDate]];
//        $where['shopId'] = $shopId;
//        $reportData = M('order_report')->where($where)->select();
//        $salesOrderSum = 0;//所有销售订单数量
//        $salesOrderMoneySum = 0;//所有销售订单总金额
//        $salesRefundOrderSum = 0;//所有订单退款数量
//        $salesRefundOrderMoneySum = 0;//所有订单退款金额
//        $real_money_total = 0;//实付金额
//        $coupon_money_total = 0;//优惠券金额
//        $goods_money_total = 0;//商品总金额
//        $need_money_total = 0;//应收金额
//        $score_money_total = 0;//积分抵扣金额
//        $delivery_money_total = 0;//配送费金额
//        $cash_money_total = 0;//余额金额
//        $wxpay_money_total = 0;//微信支付金额
//
//        $wxpay_money_out_total = 0;//微信退款金额【包含补差价、售后】
//        $cash_money_out_total = 0;//余额退款金额【包含补差价、售后】
//
//
//        $reportId_arr = array_unique(array_column($reportData, 'reportId'));
//        $orders_tab = M('orders');
//        $order_report_tab = M('sales_order_report');
//        $mod_order_complainsrecord = M('order_complainsrecord');//售后退款记录表
//        $mod_goods_pricediffe = M('goods_pricediffe');//商品差价补款表
//
//        $order_report_list = $order_report_tab->where(
//            array('reportId' => array('IN', $reportId_arr))
//        )->field('orderId')->select();
//        $order_id_arr = array_unique(array_column($order_report_list, 'orderId'));
//        $order_list = $orders_tab->where(array(
//            'orderId' => array('IN', $order_id_arr)
//        ))->select();
//        $coupon_tab = M('coupons');
//        foreach ($order_list as $item) {
//            $real_money_total += $item['realTotalMoney'];
//            if ($item['couponId'] > 0) {
//                $where = array('couponId' => $item['couponId']);
//                $coupon_info = $coupon_tab->where($where)->find();
//                if ($coupon_info) {
//                    $coupon_money_total += (float)$coupon_info['couponMoney'];
//                }
//            }
//            $goods_money_total += (float)$item['totalMoney'];
//            $need_money_total += (float)$item['needPay'];
//            $score_money_total += (float)$item['scoreMoney'];
//            $delivery_money_total += (float)$item['deliverMoney'];
//            if ($item['payFrom'] == 2) {
//                $wxpay_money_total += (float)$item['realTotalMoney'];
//
//                //根据订单支付类型查询即可 目前退款都是原路返回 余额支付就退回余额 所以记录表的支付类型暂时无用
//                $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
//                    'orderId' => $item['orderId']
//                ))->sum('money');//售后退款记录
//                $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
//                    'orderId' => $item['orderId']
//                ))->sum('money');//商品差价补款
//                $wxpay_money_out_total += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//微信退款金额【包含补差价、售后】
//
//
//            } elseif ($item['payFrom'] = 3) {
//                $cash_money_total += (float)$item['realTotalMoney'];
//
//                $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
//                    'orderId' => $item['orderId']
//                ))->sum('money');//售后退款记录
//                $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
//                    'orderId' => $item['orderId']
//                ))->sum('money');//商品差价补款
//                $cash_money_out_total += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//余额退款金额【包含补差价、售后】
//
//
//            }
//        }
//
//        //会员充值金额、套餐开通金额、优惠券购买金额 暂时合计到微信总进账里
//        $notify_log_tab = M('notify_log');
//        $notify_log_list = $notify_log_tab->where(
//            array(
//                'type' => array('IN', array(3, 4, 5)),
//                'notifyStatus' => 1,
//                'requestTime' => array('between', array($startDate . ' 00:00:00', $endDate . ' 23:59:59')),
//            )
//        )->field('id,requestJson,type,notifyStatus,requestTime')->select();
//        $set_meal_tab = M('set_meal');//套餐表
//        $coupon_set_tab = M('coupon_set');
//        foreach ($notify_log_list as $log) {
//            $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
//            $request_arr = json_decode($log['requestJson'], true);
//            if ($type == 3) {
//                $wxpay_money_total += (float)$request_arr['amount'];
//            } elseif ($type == 4) {
//                $smId = (int)$request_arr['smId'];
//                $set_meal_money = $set_meal_tab->where(
//                    array(
//                        'smId' => $smId
//                    )
//                )->getField('money');
//                $wxpay_money_total += (float)$set_meal_money;
//            } elseif ($type == 5) {
//                $csId = (int)$log['csId'];
//                $coupon_set_money = $coupon_set_tab->where(
//                    array(
//                        'csId' => $csId
//                    )
//                )->getField('nprice');
//                $wxpay_money_total += (float)$coupon_set_money;
//            }
//        }
//        foreach ($reportData as &$v) {
//            $salesOrderSum += $v['salesOrderNum'];
//            $salesOrderMoneySum += $v['salesOrderMoney'];
//            $salesRefundOrderSum += $v['salesRefundOrderNum'];
//            $salesRefundOrderMoneySum += $v['salesRefundOrderMoney'];
//            $order_report_list = $order_report_tab->where(
//                array('reportId' => $v['reportId']))->field('orderId')->select();
//            $order_id_arr = array_unique(array_column($order_report_list, 'orderId'));
//            $order_list = $orders_tab->where(array(
//                'orderId' => array('IN', $order_id_arr)
//            ))->select();
//            $real_money_signle = 0;//实付金额
//            $coupon_money_signle = 0;//优惠券金额
//            $goods_money_signle = 0;//商品总金额
//            $need_money_signle = 0;//应收金额
//            $score_money_signle = 0;//积分抵扣金额
//            $delivery_money_signle = 0;//配送费金额
//            $cash_money_signle = 0;//余额金额
//            $wxpay_money_signle = 0;//微信支付金额
//
//            $wxpay_money_out_signle = 0;//微信退款金额【包含补差价、售后】
//            $cash_money_out_signle = 0;//余额退款金额【包含补差价、售后】
//
//            foreach ($order_list as $item) {
//                $real_money_signle += $item['realTotalMoney'];
//                if ($item['couponId'] > 0) {
//                    $where = array('couponId' => $item['couponId']);
//                    $coupon_info = $coupon_tab->where($where)->find();
//                    if ($coupon_info) {
//                        $coupon_money_signle += (float)$coupon_info['couponMoney'];
//                    }
//                }
//                $goods_money_signle += (float)$item['totalMoney'];
//                $need_money_signle += (float)$item['needPay'];
//                $score_money_signle += (float)$item['scoreMoney'];
//                $delivery_money_signle += (float)$item['deliverMoney'];
//                if ($item['payFrom'] == 2) {
//                    $wxpay_money_signle += (float)$item['realTotalMoney'];
//
//                    $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
//                        'orderId' => $item['orderId']
//                    ))->sum('money');//售后退款记录
//                    $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
//                        'orderId' => $item['orderId']
//                    ))->sum('money');//商品差价补款
//                    $wxpay_money_out_signle += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//微信退款金额【包含补差价、售后】
//
//
//                } elseif ($item['payFrom'] = 3) {
//                    $cash_money_signle += (float)$item['realTotalMoney'];
//
//                    $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
//                        'orderId' => $item['orderId']
//                    ))->sum('money');//售后退款记录
//                    $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
//                        'orderId' => $item['orderId']
//                    ))->sum('money');//商品差价补款
//                    $cash_money_out_signle += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//余额退款金额【包含补差价、售后】
//
//
//                }
//            }
//            foreach ($notify_log_list as $log) {
//                if ($log['requestTime'] >= $v['reportDate'] . ' 00:00:00' and $log['requestTime'] <= $v['reportDate'] . ' 23:59:59') {
//                    $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
//                    $request_arr = json_decode($log['requestJson'], true);
//                    if ($type == 3) {
//                        $wxpay_money_signle += (float)$request_arr['amount'];
//                    } elseif ($type == 4) {
//                        $smId = (int)$request_arr['smId'];
//                        $set_meal_money = $set_meal_tab->where(
//                            array(
//                                'smId' => $smId
//                            )
//                        )->getField('money');
//                        $wxpay_money_signle += (float)$set_meal_money;
//                    } elseif ($type == 5) {
//                        $csId = (int)$log['csId'];
//                        $coupon_set_money = $coupon_set_tab->where(
//                            array(
//                                'csId' => $csId
//                            )
//                        )->getField('nprice');
//                        $wxpay_money_signle += (float)$coupon_set_money;
//                    }
//                }
//            }
//            $v['salesOrderMoney'] = $real_money_signle;
//            $v['coupon_money_signle'] = $coupon_money_signle;
//            $v['goods_money_signle'] = $goods_money_signle;
//            $v['need_money_signle'] = $need_money_signle;
//            $v['score_money_signle'] = $score_money_signle;
//            $v['delivery_money_signle'] = $delivery_money_signle;
//            $v['wxpay_money_signle'] = $wxpay_money_signle;
//            $v['cash_money_signle'] = $cash_money_signle;
//
//            $v['wxpay_money_out_signle'] = $wxpay_money_out_signle;//微信退款金额【包含补差价、售后】
//            $v['cash_money_out_signle'] = $cash_money_out_signle;//余额退款金额【包含补差价、售后】
//
//        }
//        unset($v);
//        $arrayPage = arrayPage($reportData, $page, $pageSize);
//        $list = [];
//        $list['list'] = $arrayPage;
//        $list['salesOrderSum'] = $salesOrderSum;
//        $list['salesOrderMoneySum'] = formatAmountNum($salesOrderMoneySum);
//        $list['salesRefundOrderSum'] = $salesRefundOrderSum;
//        $list['salesRefundOrderMoneySum'] = formatAmountNum($salesRefundOrderMoneySum);
//        $list['practicalPriceMoneySum'] = bc_math($salesOrderMoneySum, $salesRefundOrderMoneySum, 'bcsub', 2);
//        $list['salesOrderMoneySum'] = formatAmount($real_money_total);
//        $list['coupon_money_total'] = formatAmount($coupon_money_total);
//        $list['goods_money_total'] = formatAmount($goods_money_total);
//        $list['need_money_total'] = formatAmount($need_money_total);
//        $list['score_money_total'] = formatAmount($score_money_total);
//        $list['delivery_money_total'] = formatAmount($delivery_money_total);
//        $list['cash_money_total'] = formatAmount($cash_money_total);
//        $list['wxpay_money_total'] = formatAmount($wxpay_money_total);
//        $list['wxpay_money_out_total'] = formatAmount($wxpay_money_out_total);//微信退款金额【包含补差价、售后】
//        $list['cash_money_out_total'] = formatAmount($cash_money_out_total);//余额退款金额【包含补差价、售后】
//        if ($export == 1) {
//            $this->exportStatOrder($reportData, $list);
//            exit();
//        }
//        return $list;
//    }

//    public function statOrder($startDate, $endDate, $shopId)
//    {
//        //复制上面注释的方法,改为临时统计,业务稳定后,在做数据表统计
//        $page = (int)I('page', 1);
//        $pageSize = (int)I('pageSize', 15);
//        $export = (int)I('export', 0);//导出
//        $where = array();
//        $where['reportDate'] = ['between', [$startDate, $endDate]];
//        $where['shopId'] = $shopId;
//        $reportData = M('order_report')->where($where)->select();
//        //参考wst_order_report表
//        $salesOrderSum = 0;//所有销售订单数量A
//        $salesOrderMoneySum = 0;//所有销售订单总金额A
//        $salesRefundOrderSum = 0;//所有订单退款数量
//        $salesRefundOrderMoneySum = 0;//所有订单退款金额
//        $real_money_total = 0;//实付金额A
//        $coupon_money_total = 0;//优惠券金额A
//        $goods_money_total = 0;//商品总金额A
//        $need_money_total = 0;//应收金额A
//        $score_money_total = 0;//积分抵扣金额A
//        $delivery_money_total = 0;//配送费金额A
//        $cash_money_total = 0;//余额金额A
//        $wxpay_money_total = 0;//微信支付金额A
//
//        $wxpay_money_out_total = 0;//微信退款金额【包含补差价、售后】A
//        $cash_money_out_total = 0;//余额退款金额【包含补差价、售后】A
//
//        $wx_cash_recharge_total = 0;//微信余额充值总金额A
//        $wx_set_meal_total = 0;//微信开通会员总金额A
//        $wx_coupon_total = 0;//微信购买优惠券总金额A
//
//        $orders_tab = M('orders');
//        $order_report_tab = M('sales_order_report');
//        $mod_order_complainsrecord = M('order_complainsrecord');//售后退款记录表
//        $mod_order_complains = M('order_complains');
//        $mod_goods_pricediffe = M('goods_pricediffe');//商品差价补款表
//
//        $pay_start_time = $startDate . ' 00:00:00';
//        $pay_end_time = $endDate . ' 23:59:59';
//        $order_list = $orders_tab->where(array(
//            'shopId' => $shopId,
//            'pay_time' => array('between', array($pay_start_time, $pay_end_time)),
//            'isPay' => 1
//        ))->select();
//        foreach ($order_list as &$item) {
//            $item['pay_time'] = date('Y-m-d', strtotime($item['pay_time']));
//        }
//        unset($item);
//        $coupon_tab = M('coupons');
//        foreach ($order_list as $item) {
//            $salesOrderMoneySum += $item['realTotalMoney'];
//            $real_money_total += $item['realTotalMoney'];
//            if ($item['couponId'] > 0) {
//                $where = array('couponId' => $item['couponId']);
//                $coupon_info = $coupon_tab->where($where)->find();
//                if ($coupon_info) {
//                    $coupon_money_total += (float)$coupon_info['couponMoney'];
//                }
//            }
//            $goods_money_total += (float)$item['totalMoney'];
//            $need_money_total += (float)$item['needPay'];
//            $score_money_total += (float)$item['scoreMoney'];
//            $delivery_money_total += (float)$item['deliverMoney'];
//            if ($item['payFrom'] == 2) {
//                $wxpay_money_total += (float)$item['realTotalMoney'];
//
//                //根据订单支付类型查询即可 目前退款都是原路返回 余额支付就退回余额 所以记录表的支付类型暂时无用
////                $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
////                    'orderId' => $item['orderId'],
////                    'complainId' => array('GT', 0),
////                ))->sum('money');//售后退款记录
////                $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
////                    'orderId' => $item['orderId']
////                ))->sum('money');//商品差价补款
////                $wxpay_money_out_total += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//微信退款金额【包含补差价、售后】
//                if ($item['isRefund'] == 1) {
//                    $wxpay_money_out_total += $item['realTotalMoney'];
//                }
//
//
//            } elseif ($item['payFrom'] = 3) {
//                $cash_money_total += (float)$item['realTotalMoney'];
//
////                $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
////                    'orderId' => $item['orderId'],
////                    'complainId' => array('GT', 0),
////                ))->sum('money');//售后退款记录
////                $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
////                    'orderId' => $item['orderId']
////                ))->sum('money');//商品差价补款
////                $cash_money_out_total += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//余额退款金额【包含补差价、售后】
//                if ($item['isRefund'] == 1) {
//                    $cash_money_out_total += $item['realTotalMoney'];
//                }
//
//
//            }
//        }
//
//        //根据订单支付类型查询即可 目前退款都是原路返回 余额支付就退回余额 所以记录表的支付类型暂时无用  2：微信,3:余额
//        $order_complainsrecord_list = $mod_order_complainsrecord->where(array(
//            'addTime' => array('between', array($pay_start_time, $pay_end_time)),
//            'complainId' => array('GT', 0),
//        ))->select();//售后退款记录money
//        $order_complainsrecord_money_wx = 0;//微信 售后
//        $order_complainsrecord_money_ye = 0;//余额 售后
//        foreach ($order_complainsrecord_list as $v){
//            $orderInfo = $orders_tab->where(['orderId'=>$v['orderId']])->find();
//            if($orderInfo['payFrom'] == 2){
//                $order_complainsrecord_money_wx += $v['money'];
//            }
//            if($orderInfo['payFrom'] == 3){
//                $order_complainsrecord_money_ye += $v['money'];
//            }
//        }
//        $goods_pricediffe_list = $mod_goods_pricediffe->where(array(
//            'payTime' => array('between', array($pay_start_time, $pay_end_time)),
//            'isPay' => 1,
//        ))->select();//商品差价补款
//        $goods_pricediffe_money_wx = 0;//微信 商品差价补款
//        $goods_pricediffe_money_ye = 0;//余额 商品差价补款
//        foreach ($goods_pricediffe_list as $v){
//            $orderInfo = $orders_tab->where(['orderId'=>$v['orderId']])->find();
//            if($orderInfo['payFrom'] == 2){
//                $goods_pricediffe_money_wx += $v['money'];
//            }
//            if($orderInfo['payFrom'] == 3){
//                $goods_pricediffe_money_ye += $v['money'];
//            }
//        }
//        $wxpay_money_out_total += bc_math($order_complainsrecord_money_wx, $goods_pricediffe_money_wx, 'bcadd', 2);//微信退款金额【包含补差价、售后】
//        $cash_money_out_total += bc_math($order_complainsrecord_money_ye, $goods_pricediffe_money_ye, 'bcadd', 2);//余额退款金额【包含补差价、售后】
//
//
//        //会员充值金额、套餐开通金额、优惠券购买金额 暂时合计到微信总进账里
//        $notify_log_tab = M('notify_log');
//        $notify_log_list = $notify_log_tab->where(
//            array(
//                'type' => array('IN', array(3, 4, 5)),
//                'notifyStatus' => 1,
//                'requestTime' => array('between', array($pay_start_time, $pay_end_time)),
//            )
//        )->field('id,requestJson,type,notifyStatus,requestTime')->select();
//        foreach ($notify_log_list as &$item) {
//            $item['requestTime'] = date('Y-m-d', strtotime($item['requestTime']));
//        }
//        unset($item);
//        $set_meal_tab = M('set_meal');//套餐表
//        $coupon_set_tab = M('coupon_set');
//        foreach ($notify_log_list as $log) {
//            $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
//            $request_arr = json_decode($log['requestJson'], true);
//            if ($type == 3) {
//                $wx_cash_recharge_total += (float)$request_arr['amount'];
//            } elseif ($type == 4) {
//                $smId = (int)$request_arr['smId'];
//                $set_meal_money = $set_meal_tab->where(
//                    array(
//                        'smId' => $smId
//                    )
//                )->getField('money');
//                $wx_set_meal_total += (float)$set_meal_money;
//            } elseif ($type == 5) {
//                $csId = (int)$log['csId'];
//                $coupon_set_money = $coupon_set_tab->where(
//                    array(
//                        'csId' => $csId
//                    )
//                )->getField('nprice');
//                $wx_coupon_total += (float)$coupon_set_money;
//            }
//        }
//        //弃用统计表,直接临时计算
//        $salesOrderSum = count($order_list);
//        foreach ($reportData as &$v) {
//            //$salesOrderSum += $v['salesOrderNum'];
//            //$salesOrderMoneySum += $v['salesOrderMoney'];
//            $salesRefundOrderSum += $v['salesRefundOrderNum'];
//            $salesRefundOrderMoneySum += $v['salesRefundOrderMoney'];
////            $order_report_list = $order_report_tab->where(
////                array('reportId' => $v['reportId']))->field('orderId')->select();
////            $order_id_arr = array_unique(array_column($order_report_list, 'orderId'));
////            $order_list = $orders_tab->where(array(
////                'orderId' => array('IN', $order_id_arr)
////            ))->select();
//            $salesOrderNum_signle = 0;//订单数量
//            $real_money_signle = 0;//实付金额
//            $coupon_money_signle = 0;//优惠券金额
//            $goods_money_signle = 0;//商品总金额
//            $need_money_signle = 0;//应收金额
//            $score_money_signle = 0;//积分抵扣金额
//            $delivery_money_signle = 0;//配送费金额
//            $cash_money_signle = 0;//余额金额
//            $wxpay_money_signle = 0;//微信支付金额
//
//            $wxpay_money_out_signle = 0;//微信退款金额【包含补差价、售后】
//            $cash_money_out_signle = 0;//余额退款金额【包含补差价、售后】
//
//            foreach ($order_list as $item) {
//                if ($item['pay_time'] == $v['reportDate']) {
//                    $salesOrderNum_signle += 1;
//                    $real_money_signle += $item['realTotalMoney'];
//                    if ($item['couponId'] > 0) {
//                        $where = array('couponId' => $item['couponId']);
//                        $coupon_info = $coupon_tab->where($where)->find();
//                        if ($coupon_info) {
//                            $coupon_money_signle += (float)$coupon_info['couponMoney'];
//                        }
//                    }
//                    $goods_money_signle += (float)$item['totalMoney'];
//                    $need_money_signle += (float)$item['needPay'];
//                    $score_money_signle += (float)$item['scoreMoney'];
//                    $delivery_money_signle += (float)$item['deliverMoney'];
//                    if ($item['payFrom'] == 2) {
//                        $wxpay_money_signle += (float)$item['realTotalMoney'];
//
////                        $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
////                            'orderId' => $item['orderId']
////                        ))->sum('money');//售后退款记录
////                        $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
////                            'orderId' => $item['orderId'],
////                            'isPay' => 1
////                        ))->sum('money');//商品差价补款
////                        $wxpay_money_out_signle += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//微信退款金额【包含补差价、售后】
//
//                        if ($item['isRefund'] == 1) {
//                            $wxpay_money_out_signle += $item['realTotalMoney'];
//                        }
//
//                    } elseif ($item['payFrom'] = 3) {
//                        $cash_money_signle += (float)$item['realTotalMoney'];
//
////                        $order_complainsrecord_money = $mod_order_complainsrecord->where(array(
////                            'orderId' => $item['orderId']
////                        ))->sum('money');//售后退款记录
////                        $goods_pricediffe_money = $mod_goods_pricediffe->where(array(
////                            'orderId' => $item['orderId'],
////                            'isPay' => 1
////                        ))->sum('money');//商品差价补款
////                        $cash_money_out_signle += bc_math($order_complainsrecord_money, $goods_pricediffe_money, 'bcadd', 2);//余额退款金额【包含补差价、售后】
//                        if ($item['isRefund'] == 1) {
//                            $cash_money_out_signle += $item['realTotalMoney'];
//                        }
//
//                    }
//
//                }
//            }
//            $payStartTime = date('Y-m-d 00:00:00',strtotime($v['reportDate']));
//            $payEndTime = date('Y-m-d 23:59:59',strtotime($v['reportDate']));
//
//            $orderComplainsrecordList = $mod_order_complainsrecord->where(array(
//                'addTime' => array('between', array($payStartTime, $payEndTime)),
//                'complainId' => array('GT', 0),
//            ))->select();//售后退款记录money
//            $orderComplainsrecordMoneyWx = 0;//微信 售后
//            $orderComplainsrecordMoneyYe = 0;//余额 售后
//            foreach ($orderComplainsrecordList as $value){
//                $orderInfo = $orders_tab->where(['orderId'=>$value['orderId']])->find();
//                if($orderInfo['payFrom'] == 2){
//                    $orderComplainsrecordMoneyWx += $value['money'];
//                }
//                if($orderInfo['payFrom'] == 3){
//                    $orderComplainsrecordMoneyYe += $value['money'];
//                }
//            }
//            $goodsPricediffeList = $mod_goods_pricediffe->where(array(
//                'payTime' => array('between', array($payStartTime, $payEndTime)),
//                'isPay' => 1,
//            ))->select();//商品差价补款
//            $goodsPricediffeMoneyWx = 0;//微信 商品差价补款
//            $goodsPricediffeMoneyYe = 0;//余额 商品差价补款
//            foreach ($goodsPricediffeList as $v){
//                $orderInfo = $orders_tab->where(['orderId'=>$v['orderId']])->find();
//                if($orderInfo['payFrom'] == 2){
//                    $goodsPricediffeMoneyWx += $v['money'];
//                }
//                if($orderInfo['payFrom'] == 3){
//                    $goodsPricediffeMoneyYe += $v['money'];
//                }
//            }
//            $wxpay_money_out_signle += bc_math($orderComplainsrecordMoneyWx, $goodsPricediffeMoneyWx, 'bcadd', 2);//微信退款金额【包含补差价、售后】
//            $cash_money_out_signle += bc_math($orderComplainsrecordMoneyYe, $goodsPricediffeMoneyYe, 'bcadd', 2);//余额退款金额【包含补差价、售后】
//
//            $wx_cash_recharge_signle = 0;
//            $wx_set_meal_signle = 0;
//            $wx_coupon_signle = 0;
//            foreach ($notify_log_list as $log) {
//                if ($log['requestTime'] == $v['reportDate']) {
//                    $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
//                    $request_arr = json_decode($log['requestJson'], true);
//                    if ($type == 3) {
//                        $wx_cash_recharge_signle += (float)$request_arr['amount'];
//                        $wxpay_money_total += (float)$request_arr['amount'];
//                        $wxpay_money_signle += (float)$request_arr['amount'];
//                    } elseif ($type == 4) {
//                        $smId = (int)$request_arr['smId'];
//                        $set_meal_money = $set_meal_tab->where(
//                            array(
//                                'smId' => $smId
//                            )
//                        )->getField('money');
//                        $wx_set_meal_signle += (float)$set_meal_money;
//                        $wxpay_money_total += (float)$set_meal_money;
//                        $wxpay_money_signle += (float)$set_meal_money;
//                    } elseif ($type == 5) {
//                        $csId = (int)$log['csId'];
//                        $coupon_set_money = $coupon_set_tab->where(
//                            array(
//                                'csId' => $csId
//                            )
//                        )->getField('nprice');
//                        $wx_coupon_signle += (float)$coupon_set_money;
//                        $wxpay_money_total += (float)$coupon_set_money;
//                        $wxpay_money_signle += (float)$coupon_set_money;
//                    }
//                }
//            }
//            $v['salesOrderNum'] = $salesOrderNum_signle;
//            $v['salesOrderMoney'] = $real_money_signle;
//            $v['coupon_money_signle'] = $coupon_money_signle;
//            $v['goods_money_signle'] = $goods_money_signle;
//            $v['need_money_signle'] = $need_money_signle;
//            $v['score_money_signle'] = $score_money_signle;
//            $v['delivery_money_signle'] = $delivery_money_signle;
//            $v['wxpay_money_signle'] = $wxpay_money_signle;
//            $v['cash_money_signle'] = $cash_money_signle;
//
//            $v['wxpay_money_out_signle'] = $wxpay_money_out_signle;//微信退款金额【包含补差价、售后】
//            $v['cash_money_out_signle'] = $cash_money_out_signle;//余额退款金额【包含补差价、售后】
//
//            $v['wx_cash_recharge_signle'] = $wx_cash_recharge_signle;//微信余额充值金额
//            $v['wx_set_meal_signle'] = $wx_set_meal_signle;//微信开通会员金额
//            $v['wx_coupon_signle'] = $wx_coupon_signle;//微信购买优惠券金额
//
//            $v['wxpay_money_real_signle'] = bc_math($wxpay_money_signle, $wxpay_money_out_signle, 'bcsub', 2);//微信实际到账金额
//            $v['cash_money_real_signle'] = bc_math($cash_money_signle, $cash_money_out_signle, 'bcsub', 2);//余额实际到账金额
//        }
//        unset($v);
//        $arrayPage = arrayPage($reportData, $page, $pageSize);
//        $list = [];
//        $list['list'] = $arrayPage;
//        $list['salesOrderSum'] = $salesOrderSum;
//        $list['salesOrderMoneySum'] = formatAmountNum($salesOrderMoneySum);
//        $list['salesRefundOrderSum'] = $salesRefundOrderSum;
//        $list['salesRefundOrderMoneySum'] = formatAmountNum($salesRefundOrderMoneySum);
//        $list['practicalPriceMoneySum'] = bc_math($salesOrderMoneySum, $salesRefundOrderMoneySum, 'bcsub', 2);
//        $list['salesOrderMoneySum'] = formatAmount($real_money_total);
//        $list['coupon_money_total'] = formatAmount($coupon_money_total);
//        $list['goods_money_total'] = formatAmount($goods_money_total);
//        $list['need_money_total'] = formatAmount($need_money_total);
//        $list['score_money_total'] = formatAmount($score_money_total);
//        $list['delivery_money_total'] = formatAmount($delivery_money_total);
//        $list['cash_money_total'] = formatAmount($cash_money_total);
//        $list['wxpay_money_total'] = formatAmount($wxpay_money_total);
//        $list['wxpay_money_out_total'] = formatAmount($wxpay_money_out_total);//微信退款金额【包含补差价、售后】
//        $list['cash_money_out_total'] = formatAmount($cash_money_out_total);//余额退款金额【包含补差价、售后】
//
//        $list['wx_cash_recharge_total'] = formatAmount($wx_cash_recharge_total);//微信余额充值总金额
//        $list['wx_set_meal_total'] = formatAmount($wx_set_meal_total);//微信开通会员总金额
//        $list['wx_coupon_total'] = formatAmount($wx_coupon_total);//微信购买优惠券总金额
//        $list['cash_money_real_total'] = bc_math($cash_money_total, $cash_money_out_total, 'bcsub', 2);//余额实际到账总金额
//        $list['wxpay_money_real_total'] = bc_math($wxpay_money_total, $wxpay_money_out_total, 'bcsub', 2);//微信实际到账总金额
//        if ($export == 1) {
//            $this->exportStatOrder($reportData, $list);
//            exit();
//        }
//        return $list;
//    }

    public function statOrder($startDate, $endDate, $shopId)
    {
        //复制上面注释的方法,改为临时统计,业务稳定后,在做数据表统计
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $where = array();
        $where['reportDate'] = ['between', [$startDate, $endDate]];
        $where['shopId'] = $shopId;
        $reportData = M('order_report')->where($where)->select();
        $order_module = new OrdersModule();
        //参考wst_order_report表
        $salesOrderSum = 0;//所有销售订单数量A
        $salesOrderMoneySum = 0;//所有销售订单总金额A
        $salesRefundOrderSum = 0;//所有订单退款数量
        $salesRefundOrderMoneySum = 0;//所有订单退款金额
        $real_money_total = 0;//实付金额A
        $coupon_money_total = 0;//优惠券金额A
        $goods_money_total = 0;//商品总金额A
        $need_money_total = 0;//应收金额A
        $score_money_total = 0;//积分抵扣金额A
        $delivery_money_total = 0;//配送费金额A
        $cash_money_total = 0;//余额金额A
        $wxpay_money_total = 0;//微信支付金额A
        $alipay_money_total = 0;//支付宝支付金额A

        $wxpay_money_out_total = 0;//微信退款金额【包含补差价、售后】A
        $cash_money_out_total = 0;//余额退款金额【包含补差价、售后】A
        $alipay_money_out_total = 0;//支付宝退款金额【包含补差价、售后】A

        $wx_cash_recharge_total = 0;//微信余额充值总金额A
        $wx_set_meal_total = 0;//微信开通会员总金额A
        $wx_coupon_total = 0;//微信购买优惠券总金额A

        $alipay_cash_recharge_total = 0;//支付宝余额充值总金额A
        $alipay_set_meal_total = 0;//支付宝开通会员总金额A
        $alipay_coupon_total = 0;//支付宝购买优惠券总金额A

        $orders_tab = M('orders');
        $order_report_tab = M('sales_order_report');
        $mod_order_complainsrecord = M('order_complainsrecord');//售后退款记录表
        $mod_order_complains = M('order_complains');
        $mod_goods_pricediffe = M('goods_pricediffe');//商品差价补款表

        $pay_start_time = $startDate . ' 00:00:00';
        $pay_end_time = $endDate . ' 23:59:59';
        $order_list = $orders_tab->where(array(
            'shopId' => $shopId,
            'pay_time' => array('between', array($pay_start_time, $pay_end_time)),
            'isPay' => 1
        ))->select();
        foreach ($order_list as &$item) {
            $item['pay_time'] = date('Y-m-d', strtotime($item['pay_time']));
        }
        unset($item);
        $order_list_map = [];
        foreach ($order_list as $order_list_row) {
            $order_list_map[$order_list_row['orderId']] = $order_list_row;
        }
//        $coupon_tab = M('coupons');
        $orderIdArr = array_column($order_list, 'orderId');
        foreach ($order_list as $item) {
            $salesOrderMoneySum += $item['realTotalMoney'];
            $real_money_total += $item['realTotalMoney'];
//            if ($item['couponId'] > 0) {
//                $where = array('couponId' => $item['couponId']);
//                $coupon_info = $coupon_tab->where($where)->find();
//                if ($coupon_info) {
//                    $coupon_money_total += (float)$coupon_info['couponMoney'];
//                }
//            }
            $coupon_money_total += (float)$item['coupon_use_money'];
            $coupon_money_total += (float)$item['delivery_coupon_use_money'];

            $goods_money_total += (float)$item['totalMoney'];
            $need_money_total += (float)$item['needPay'];
            $score_money_total += (float)$item['scoreMoney'];
            $delivery_money_total += (float)$item['deliverMoney'];
            if ($item['payFrom'] == 1) {
                $alipay_money_total += (float)$item['realTotalMoney'];
                if ($item['isRefund'] == 1) {
                    $alipay_money_out_total += $item['realTotalMoney'];
                }
            } elseif ($item['payFrom'] == 2) {
                $wxpay_money_total += (float)$item['realTotalMoney'];
                if ($item['isRefund'] == 1) {
                    $wxpay_money_out_total += $item['realTotalMoney'];
                }
            } elseif ($item['payFrom'] = 3) {
                $cash_money_total += (float)$item['realTotalMoney'];
                if ($item['isRefund'] == 1) {
                    $cash_money_out_total += $item['realTotalMoney'];
                }
            }
        }
        //根据订单支付类型查询即可 目前退款都是原路返回 余额支付就退回余额 所以记录表的支付类型暂时无用  2：微信,3:余额
        $order_complainsrecord_list = $mod_order_complainsrecord->where(array(
            'addTime' => array('between', array($pay_start_time, $pay_end_time)),
            'complainId' => array('GT', 0),
            'orderId' => array('in', $orderIdArr)
        ))->select();//售后退款记录money
        $order_complainsrecord_money_wx = 0;//微信 售后
        $order_complainsrecord_money_ye = 0;//余额 售后
        $order_complainsrecord_money_alipay = 0;//支付宝 售后
        foreach ($order_complainsrecord_list as $v) {
//            $orderInfo = $orders_tab->where(['orderId' => $v['orderId']])->find();
            $orderInfo = $order_list_map[$v['orderId']];
            if ($orderInfo['payFrom'] == 1) {
                $order_complainsrecord_money_alipay += $v['money'];
            }
            if ($orderInfo['payFrom'] == 2) {
                $order_complainsrecord_money_wx += $v['money'];
            }
            if ($orderInfo['payFrom'] == 3) {
                $order_complainsrecord_money_ye += $v['money'];
            }
        }
        $goods_pricediffe_list = $mod_goods_pricediffe->where(array(
            'payTime' => array('between', array($pay_start_time, $pay_end_time)),
            'isPay' => 1,
            'orderId' => array('in', $orderIdArr)
        ))->select();//商品差价补款
        $goods_pricediffe_money_wx = 0;//微信 商品差价补款
        $goods_pricediffe_money_ye = 0;//余额 商品差价补款
        $goods_pricediffe_money_alipay = 0;//支付宝 商品差价补款
        foreach ($goods_pricediffe_list as $v) {
//            $orderInfo = $orders_tab->where(['orderId' => $v['orderId']])->find();
            $orderInfo = $order_list_map[$v['orderId']];
            if ($orderInfo['payFrom'] == 1) {
                $goods_pricediffe_money_alipay += $v['money'];
            }
            if ($orderInfo['payFrom'] == 2) {
                $goods_pricediffe_money_wx += $v['money'];
            }
            if ($orderInfo['payFrom'] == 3) {
                $goods_pricediffe_money_ye += $v['money'];
            }
        }
        $wxpay_money_out_total += bc_math($order_complainsrecord_money_wx, $goods_pricediffe_money_wx, 'bcadd', 2);//微信退款金额【包含补差价、售后】
        $cash_money_out_total += bc_math($order_complainsrecord_money_ye, $goods_pricediffe_money_ye, 'bcadd', 2);//余额退款金额【包含补差价、售后】
        $alipay_money_out_total += bc_math($order_complainsrecord_money_alipay, $goods_pricediffe_money_alipay, 'bcadd', 2);//支付宝退款金额【包含补差价、售后】

        //会员充值金额、套餐开通金额、优惠券购买金额 暂时合计到微信总进账里
        $notify_log_tab = M('notify_log');
        $notify_log_list = $notify_log_tab->where(
            array(
                'type' => array('IN', array(3, 4, 5)),
                'notifyStatus' => 1,
                'requestTime' => array('between', array($pay_start_time, $pay_end_time)),
            )
        )->field('id,requestJson,type,notifyStatus,requestTime')->select();
        foreach ($notify_log_list as &$item) {
            $item['requestTime'] = date('Y-m-d', strtotime($item['requestTime']));
        }
        unset($item);
        $set_meal_tab = M('set_meal');//套餐表
        $coupon_set_tab = M('coupon_set');
        $smId_arr = [];
        $set_meal_list_map = [];
        $csId_arr = [];
        $coupon_set_list_map = [];
        foreach ($notify_log_list as $notify_log_list_row) {
            $type = $notify_log_list_row['type'];
            $request_arr = json_decode($notify_log_list_row['requestJson'], true);
            if ($type == 4) {
                $smId_arr[] = (int)$request_arr['smId'];
            } elseif ($type == 5) {
                $csId_arr[] = (int)$request_arr['csId'];
            }
        }
        if (count($smId_arr) > 0) {
            $smId_arr = array_unique($smId_arr);
            $set_meal_list = $set_meal_tab->where(
                array('smId' => array('IN', $smId_arr))
            )->select();
            foreach ($set_meal_list as $set_meal_list_row) {
                $set_meal_list_map[$set_meal_list_row['smId']] = $set_meal_list_row;
            }
        }
        if (count($csId_arr) > 0) {
            $csId_arr = array_unique($csId_arr);
            $coupon_set_list = $coupon_set_tab->where(
                array('csId' => array('in', $csId_arr))
            )->select();
            foreach ($coupon_set_list as $coupon_set_list_row) {
                $coupon_set_list_map[$coupon_set_list_row['csId']] = $coupon_set_list_row;
            }
        }
        foreach ($notify_log_list as $log) {
            $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
            $request_arr = json_decode($log['requestJson'], true);
            $pay_type = (int)$request_arr['payType'];
            if ($type == 3) {
                if ($pay_type == 1) {
                    $alipay_cash_recharge_total += (float)$request_arr['amount'];
                } elseif ($pay_type == 2) {
                    $wx_cash_recharge_total += (float)$request_arr['amount'];
                }
            } elseif ($type == 4) {
                $smId = (int)$request_arr['smId'];
//                $set_meal_money = $set_meal_tab->where(
//                    array(
//                        'smId' => $smId
//                    )
//                )->getField('money');
                $set_meal_money = $set_meal_list_map[$smId]['money'];
                if ($pay_type == 1) {
                    $alipay_set_meal_total += (float)$set_meal_money;
                } elseif ($pay_type == 2) {
                    $wx_set_meal_total += (float)$set_meal_money;
                }
            } elseif ($type == 5) {
                $csId = (int)$log['csId'];
//                $coupon_set_money = $coupon_set_tab->where(
//                    array(
//                        'csId' => $csId
//                    )
//                )->getField('nprice');
                $coupon_set_money = $coupon_set_list_map[$csId]['nprice'];
                if ($pay_type == 1) {
                    $alipay_coupon_total += (float)$coupon_set_money;
                } elseif ($pay_type == 2) {
                    $wx_coupon_total += (float)$coupon_set_money;
                }
            }
        }
        $income_total = 0;//收入
        $salesOrderSum = count($order_list);
        foreach ($reportData as &$v) {
            $salesRefundOrderSum += $v['salesRefundOrderNum'];
            $salesRefundOrderMoneySum += $v['salesRefundOrderMoney'];
            $salesOrderNum_signle = 0;//订单数量
            $real_money_signle = 0;//实付金额
            $coupon_money_signle = 0;//优惠券金额
            $goods_money_signle = 0;//商品总金额
            $need_money_signle = 0;//应收金额
            $score_money_signle = 0;//积分抵扣金额
            $delivery_money_signle = 0;//配送费金额
            $cash_money_signle = 0;//余额金额
            $wxpay_money_signle = 0;//微信支付金额
            $alipay_money_signle = 0;//支付宝支付金额

            $wxpay_money_out_signle = 0;//微信退款金额【包含补差价、售后】
            $cash_money_out_signle = 0;//余额退款金额【包含补差价、售后】
            $alipay_money_out_signle = 0;//支付宝退款金额【包含补差价、售后】

            foreach ($order_list as $item) {
                if ($item['pay_time'] == $v['reportDate'] && $item["shopId"] == $v['shopId']) {
                    $salesOrderNum_signle += 1;
                    $real_money_signle += $item['realTotalMoney'];
//                    if ($item['couponId'] > 0) {
//                        $where = array('couponId' => $item['couponId']);
//                        $coupon_info = $coupon_tab->where($where)->find();
//                        if ($coupon_info) {
//                            $coupon_money_signle += (float)$coupon_info['couponMoney'];
//                        }
//                    }
                    $coupon_money_signle += (float)$item['coupon_use_money'];
                    $coupon_money_signle += (float)$item['delivery_coupon_use_money'];

                    $goods_money_signle += (float)$item['totalMoney'];
                    $need_money_signle += (float)$item['needPay'];
                    $score_money_signle += (float)$item['scoreMoney'];
                    $delivery_money_signle += (float)$item['deliverMoney'];
                    if ($item['payFrom'] == 1) {
                        $alipay_money_signle += (float)$item['realTotalMoney'];
                        if ($item['isRefund'] == 1) {
                            $alipay_money_out_signle += $item['realTotalMoney'];
                        }
                    } elseif ($item['payFrom'] == 2) {
                        $wxpay_money_signle += (float)$item['realTotalMoney'];
                        if ($item['isRefund'] == 1) {
                            $wxpay_money_out_signle += $item['realTotalMoney'];
                        }
                    } elseif ($item['payFrom'] = 3) {
                        $cash_money_signle += (float)$item['realTotalMoney'];
                        if ($item['isRefund'] == 1) {
                            $cash_money_out_signle += $item['realTotalMoney'];
                        }
                    }
                }
            }

            $payStartTime = date('Y-m-d 00:00:00', strtotime($v['reportDate']));
            $payEndTime = date('Y-m-d 23:59:59', strtotime($v['reportDate']));

            $whereInfo = [];
            $whereInfo['oc.addTime'] = ['between', [$payStartTime, $payEndTime]];
            $whereInfo['oc.complainId'] = ['GT', 0];
            //店铺名称|编号
            if (!empty(I('shopWords'))) {
                $shopWords = I('shopWords');
                $maps['s.shopName'] = ['like', "%{$shopWords}%"];
                $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
                $maps['_logic'] = 'OR';
                $whereInfo['_complex'] = $maps;
            }
            $orderComplainsrecordList = $mod_order_complainsrecord->alias('oc')
                ->join("left join wst_orders wo on wo.orderId = oc.orderId")
                ->join('left join wst_shops s on s.shopId = wo.shopId')
                ->where($whereInfo)
                ->field('oc.*')
                ->select();//售后退款记录money


            $orderComplainsrecordMoneyWx = 0;//微信 售后
            $orderComplainsrecordMoneyYe = 0;//余额 售后
            $orderComplainsrecordMoneyAlipay = 0;//支付宝 售后
            $order_complains_orders_list_map = [];
            $order_complains_orders_id_arr = array_column($orderComplainsrecordList, 'orderId');
            if (count($order_complains_orders_id_arr) > 0) {
                $order_complains_orders_id_arr = array_unique($order_complains_orders_id_arr);
                $order_complains_orders_list = $order_module->getOrderListById($order_complains_orders_id_arr);
                foreach ($order_complains_orders_list as $order_complains_orders_list_row) {
                    $order_complains_orders_list_map[$order_complains_orders_list_row['orderId']] = $order_complains_orders_list_row;
                }
            }
            foreach ($orderComplainsrecordList as $value) {
//                $orderInfo = $orders_tab->where(['orderId' => $value['orderId']])->find();
                $orderInfo = $order_complains_orders_list_map[$value['orderId']];
                if ($orderInfo['payFrom'] == 1) {
                    $orderComplainsrecordMoneyAlipay += $value['money'];
                }
                if ($orderInfo['payFrom'] == 2) {
                    $orderComplainsrecordMoneyWx += $value['money'];
                }
                if ($orderInfo['payFrom'] == 3) {
                    $orderComplainsrecordMoneyYe += $value['money'];
                }
            }
            $whereInfo = [];
            $whereInfo['gp.payTime'] = ['between', [$payStartTime, $payEndTime]];
            $whereInfo['gp.isPay'] = 1;
            //店铺名称|编号
            if (!empty(I('shopWords'))) {
                $shopWords = I('shopWords');
                $maps['s.shopName'] = ['like', "%{$shopWords}%"];
                $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
                $maps['_logic'] = 'OR';
                $whereInfo['_complex'] = $maps;
            }
            $goodsPricediffeList = $mod_goods_pricediffe->alias('gp')
                ->join("left join wst_orders wo on wo.orderId = gp.orderId")
                ->join('left join wst_shops s on s.shopId = wo.shopId')
                ->where($whereInfo)
                ->field('gp.*')
                ->select();//商品差价补款
            $goods_pricediffe_orders_list_map = [];
            $goods_pricediffe_orders_id_arr = array_column($goodsPricediffeList, 'orderId');
            if (count($goods_pricediffe_orders_id_arr) > 0) {
                $goods_pricediffe_orders_id_arr = array_unique($goods_pricediffe_orders_id_arr);
                $goods_pricediffe_orders_list = $order_module->getOrderListById($goods_pricediffe_orders_id_arr);
                foreach ($goods_pricediffe_orders_list as $goods_pricediffe_orders_list_row) {
                    $goods_pricediffe_orders_list_map[$goods_pricediffe_orders_list_row['orderId']] = $goods_pricediffe_orders_list_row;
                }
            }

            $goodsPricediffeMoneyWx = 0;//微信 商品差价补款
            $goodsPricediffeMoneyYe = 0;//余额 商品差价补款
            $goodsPricediffeMoneyAlipay = 0;//支付宝 商品差价补款
            foreach ($goodsPricediffeList as $value) {
//                $orderInfo = $orders_tab->where(['orderId' => $value['orderId']])->find();
                $orderInfo = $goods_pricediffe_orders_list_map[$value['orderId']];
                if ($orderInfo['payFrom'] == 1) {
                    $goodsPricediffeMoneyAlipay += $value['money'];
                }
                if ($orderInfo['payFrom'] == 2) {
                    $goodsPricediffeMoneyWx += $value['money'];
                }
                if ($orderInfo['payFrom'] == 3) {
                    $goodsPricediffeMoneyYe += $value['money'];
                }
            }
            $wxpay_money_out_signle += bc_math($orderComplainsrecordMoneyWx, $goodsPricediffeMoneyWx, 'bcadd', 2);//微信退款金额【包含补差价、售后】
            $cash_money_out_signle += bc_math($orderComplainsrecordMoneyYe, $goodsPricediffeMoneyYe, 'bcadd', 2);//余额退款金额【包含补差价、售后】
            $alipay_money_out_signle += bc_math($orderComplainsrecordMoneyAlipay, $goodsPricediffeMoneyAlipay, 'bcadd', 2);//支付宝退款金额【包含补差价、售后】

            $wx_cash_recharge_signle = 0;
            $wx_set_meal_signle = 0;
            $wx_coupon_signle = 0;
            $alipay_cash_recharge_signle = 0;
            $alipay_set_meal_signle = 0;
            $alipay_coupon_signle = 0;
            foreach ($notify_log_list as $log) {
                if ($log['requestTime'] == $v['reportDate']) {
                    $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
                    $request_arr = json_decode($log['requestJson'], true);
                    $pay_type = (int)$request_arr['payType'];
                    if ($type == 3) {
                        if ($pay_type == 1) {
                            $alipay_cash_recharge_signle += (float)$request_arr['amount'];
                            $alipay_money_total += (float)$request_arr['amount'];
                            $alipay_money_signle += (float)$request_arr['amount'];
                        } elseif ($pay_type == 2) {
                            $wx_cash_recharge_signle += (float)$request_arr['amount'];
                            $wxpay_money_total += (float)$request_arr['amount'];
                            $wxpay_money_signle += (float)$request_arr['amount'];
                        }
                    } elseif ($type == 4) {
                        $smId = (int)$request_arr['smId'];
//                        $set_meal_money = $set_meal_tab->where(
//                            array(
//                                'smId' => $smId
//                            )
//                        )->getField('money');
                        $set_meal_money = $set_meal_list_map[$smId]['money'];
                        if ($pay_type == 1) {
                            $alipay_set_meal_signle += (float)$set_meal_money;
                            $alipay_money_total += (float)$set_meal_money;
                            $alipay_money_signle += (float)$set_meal_money;
                        } elseif ($pay_type == 2) {
                            $wx_set_meal_signle += (float)$set_meal_money;
                            $wxpay_money_total += (float)$set_meal_money;
                            $wxpay_money_signle += (float)$set_meal_money;
                        }

                    } elseif ($type == 5) {
                        $csId = (int)$log['csId'];
//                        $coupon_set_money = $coupon_set_tab->where(
//                            array(
//                                'csId' => $csId
//                            )
//                        )->getField('nprice');
                        $coupon_set_money = $coupon_set_list_map[$csId]['nprice'];
                        if ($pay_type == 1) {
                            $alipay_coupon_signle += (float)$coupon_set_money;
                            $alipay_money_total += (float)$coupon_set_money;
                            $alipay_money_signle += (float)$coupon_set_money;
                        } elseif ($pay_type == 2) {
                            $wx_coupon_signle += (float)$coupon_set_money;
                            $wxpay_money_total += (float)$coupon_set_money;
                            $wxpay_money_signle += (float)$coupon_set_money;
                        }
                    }
                }
            }
            $v['salesOrderNum'] = $salesOrderNum_signle;
            $v['salesOrderMoney'] = $real_money_signle;
            $v['coupon_money_signle'] = $coupon_money_signle;
            $v['goods_money_signle'] = $goods_money_signle;
            $v['need_money_signle'] = $need_money_signle;
            $v['score_money_signle'] = $score_money_signle;
            $v['delivery_money_signle'] = $delivery_money_signle;
            $v['wxpay_money_signle'] = $wxpay_money_signle;
            $v['cash_money_signle'] = $cash_money_signle;
            $v['alipay_money_signle'] = $alipay_money_signle;

            $v['wxpay_money_out_signle'] = $wxpay_money_out_signle;//微信退款金额【包含补差价、售后】
            $v['cash_money_out_signle'] = $cash_money_out_signle;//余额退款金额【包含补差价、售后】
            $v['alipay_money_out_signle'] = $alipay_money_out_signle;//支付宝退款金额【包含补差价、售后】

            $v['wx_cash_recharge_signle'] = $wx_cash_recharge_signle;//微信余额充值金额
            $v['wx_set_meal_signle'] = $wx_set_meal_signle;//微信开通会员金额
            $v['wx_coupon_signle'] = $wx_coupon_signle;//微信购买优惠券金额

            $v['alipay_cash_recharge_signle'] = $alipay_cash_recharge_signle;//支付宝余额充值金额
            $v['alipay_set_meal_signle'] = $alipay_set_meal_signle;//支付宝开通会员金额
            $v['alipay_coupon_signle'] = $alipay_coupon_signle;//支付宝购买优惠券金额

            $v['wxpay_money_real_signle'] = bc_math($wxpay_money_signle, $wxpay_money_out_signle, 'bcsub', 2);//微信实际到账金额
            $v['cash_money_real_signle'] = bc_math($cash_money_signle, $cash_money_out_signle, 'bcsub', 2);//余额实际到账金额
            $v['alipay_money_real_signle'] = bc_math($alipay_money_signle, $alipay_money_out_signle, 'bcsub', 2);//支付宝实际到账金额

            $v['income'] = bc_math($real_money_signle, $wxpay_money_out_signle, 'bcsub', 2);//收入 减去微信退款
            $v['income'] = bc_math($v['income'], $cash_money_out_signle, 'bcsub', 2);//收入 减去余额退款
            $v['income'] = bc_math($v['income'], $alipay_money_out_signle, 'bcsub', 2);//收入 减去支付宝退款
            $income_total += $v['income'];

        }
        unset($v);
        $arrayPage = arrayPage($reportData, $page, $pageSize);
        $list = [];
        $list['list'] = $arrayPage;
        $list['salesOrderSum'] = $salesOrderSum;
        $list['salesOrderMoneySum'] = formatAmountNum($salesOrderMoneySum);
        $list['salesRefundOrderSum'] = $salesRefundOrderSum;
        $list['salesRefundOrderMoneySum'] = formatAmountNum($salesRefundOrderMoneySum);
        $list['practicalPriceMoneySum'] = bc_math($salesOrderMoneySum, $salesRefundOrderMoneySum, 'bcsub', 2);
        $list['salesOrderMoneySum'] = formatAmount($real_money_total);
        $list['coupon_money_total'] = formatAmount($coupon_money_total);
        $list['goods_money_total'] = formatAmount($goods_money_total);
        $list['need_money_total'] = formatAmount($need_money_total);
        $list['score_money_total'] = formatAmount($score_money_total);
        $list['delivery_money_total'] = formatAmount($delivery_money_total);
        $list['cash_money_total'] = formatAmount($cash_money_total);
        $list['wxpay_money_total'] = formatAmount($wxpay_money_total);
        $list['alipay_money_total'] = formatAmount($alipay_money_total);
        $list['wxpay_money_out_total'] = formatAmount($wxpay_money_out_total);//微信退款金额【包含补差价、售后】
        $list['cash_money_out_total'] = formatAmount($cash_money_out_total);//余额退款金额【包含补差价、售后】
        $list['alipay_money_out_total'] = formatAmount($alipay_money_out_total);//支付宝退款金额【包含补差价、售后】

        $list['wx_cash_recharge_total'] = formatAmount($wx_cash_recharge_total);//微信余额充值总金额
        $list['wx_set_meal_total'] = formatAmount($wx_set_meal_total);//微信开通会员总金额
        $list['wx_coupon_total'] = formatAmount($wx_coupon_total);//微信购买优惠券总金额

        $list['alipay_cash_recharge_total'] = formatAmount($alipay_cash_recharge_total);//支付宝余额充值总金额
        $list['alipay_set_meal_total'] = formatAmount($alipay_set_meal_total);//支付宝开通会员总金额
        $list['alipay_coupon_total'] = formatAmount($alipay_coupon_total);//支付宝购买优惠券总金额

        $list['cash_money_real_total'] = bc_math($cash_money_total, $cash_money_out_total, 'bcsub', 2);//余额实际到账总金额
        $list['wxpay_money_real_total'] = bc_math($wxpay_money_total, $wxpay_money_out_total, 'bcsub', 2);//微信实际到账总金额
        $list['alipay_money_real_total'] = bc_math($alipay_money_total, $alipay_money_out_total, 'bcsub', 2);//支付宝实际到账总金额
        $list['income_total'] = $income_total;//收入
        // //弃用统计表,直接临时计算
        // $salesOrderSum = count($order_list);
        // foreach ($reportData as &$v) {
        //     $salesRefundOrderSum += $v['salesRefundOrderNum'];
        //     $salesRefundOrderMoneySum += $v['salesRefundOrderMoney'];
        //     $salesOrderNum_signle = 0;//订单数量
        //     $real_money_signle = 0;//实付金额
        //     $coupon_money_signle = 0;//优惠券金额
        //     $goods_money_signle = 0;//商品总金额
        //     $need_money_signle = 0;//应收金额
        //     $score_money_signle = 0;//积分抵扣金额
        //     $delivery_money_signle = 0;//配送费金额
        //     $cash_money_signle = 0;//余额金额
        //     $wxpay_money_signle = 0;//微信支付金额

        //     $wxpay_money_out_signle = 0;//微信退款金额【包含补差价、售后】
        //     $cash_money_out_signle = 0;//余额退款金额【包含补差价、售后】

        //     foreach ($order_list as $item) {
        //         if ($item['pay_time'] == $v['reportDate']) {
        //             $salesOrderNum_signle += 1;
        //             $real_money_signle += $item['realTotalMoney'];
        //             if ($item['couponId'] > 0) {
        //                 $where = array('couponId' => $item['couponId']);
        //                 $coupon_info = $coupon_tab->where($where)->find();
        //                 if ($coupon_info) {
        //                     $coupon_money_signle += (float)$coupon_info['couponMoney'];
        //                 }
        //             }
        //             $goods_money_signle += (float)$item['totalMoney'];
        //             $need_money_signle += (float)$item['needPay'];
        //             $score_money_signle += (float)$item['scoreMoney'];
        //             $delivery_money_signle += (float)$item['deliverMoney'];
        //             if ($item['payFrom'] == 2) {
        //                 $wxpay_money_signle += (float)$item['realTotalMoney'];
        //                 if ($item['isRefund'] == 1) {
        //                     $wxpay_money_out_signle += $item['realTotalMoney'];
        //                 }
        //             } elseif ($item['payFrom'] = 3) {
        //                 $cash_money_signle += (float)$item['realTotalMoney'];
        //                 if ($item['isRefund'] == 1) {
        //                     $cash_money_out_signle += $item['realTotalMoney'];
        //                 }
        //             }
        //         }
        //     }

        //     $payStartTime = date('Y-m-d 00:00:00', strtotime($v['reportDate']));
        //     $payEndTime = date('Y-m-d 23:59:59', strtotime($v['reportDate']));


        //     $orderComplainsrecordList = $mod_order_complainsrecord->where(array(
        //         'addTime' => array('between', array($payStartTime, $payEndTime)),
        //         'complainId' => array('GT', 0),
        //         'orderId' => array('in', $orderIdArr)
        //     ))->select();//售后退款记录money
        //     $orderComplainsrecordMoneyWx = 0;//微信 售后
        //     $orderComplainsrecordMoneyYe = 0;//余额 售后
        //     foreach ($orderComplainsrecordList as $value) {
        //         $orderInfo = $orders_tab->where(['orderId' => $value['orderId']])->find();
        //         if ($orderInfo['payFrom'] == 2) {
        //             $orderComplainsrecordMoneyWx += $value['money'];
        //         }
        //         if ($orderInfo['payFrom'] == 3) {
        //             $orderComplainsrecordMoneyYe += $value['money'];
        //         }
        //     }
        //     $goodsPricediffeList = $mod_goods_pricediffe->where(array(
        //         'payTime' => array('between', array($payStartTime, $payEndTime)),
        //         'isPay' => 1,
        //         'orderId' => array('in', $orderIdArr)
        //     ))->select();//商品差价补款
        //     $goodsPricediffeMoneyWx = 0;//微信 商品差价补款
        //     $goodsPricediffeMoneyYe = 0;//余额 商品差价补款
        //     foreach ($goodsPricediffeList as $value) {
        //         $orderInfo = $orders_tab->where(['orderId' => $value['orderId']])->find();
        //         if ($orderInfo['payFrom'] == 2) {
        //             $goodsPricediffeMoneyWx += $value['money'];
        //         }
        //         if ($orderInfo['payFrom'] == 3) {
        //             $goodsPricediffeMoneyYe += $value['money'];
        //         }
        //     }
        //     $wxpay_money_out_signle += bc_math($orderComplainsrecordMoneyWx, $goodsPricediffeMoneyWx, 'bcadd', 2);//微信退款金额【包含补差价、售后】
        //     $cash_money_out_signle += bc_math($orderComplainsrecordMoneyYe, $goodsPricediffeMoneyYe, 'bcadd', 2);//余额退款金额【包含补差价、售后】

        //     $wx_cash_recharge_signle = 0;
        //     $wx_set_meal_signle = 0;
        //     $wx_coupon_signle = 0;


        //     foreach ($notify_log_list as $log) {

        //         if ($log['requestTime'] == $v['reportDate']) {
        //             $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
        //             $request_arr = json_decode($log['requestJson'], true);
        //             if ($type == 3) {
        //                 $wx_cash_recharge_signle += (float)$request_arr['amount'];
        //                 $wxpay_money_total += (float)$request_arr['amount'];
        //                 $wxpay_money_signle += (float)$request_arr['amount'];
        //             } elseif ($type == 4) {
        //                 $smId = (int)$request_arr['smId'];
        //                 $set_meal_money = $set_meal_tab->where(
        //                     array(
        //                         'smId' => $smId
        //                     )
        //                 )->getField('money');
        //                 $wx_set_meal_signle += (float)$set_meal_money;
        //                 $wxpay_money_total += (float)$set_meal_money;
        //                 $wxpay_money_signle += (float)$set_meal_money;
        //             } elseif ($type == 5) {
        //                 $csId = (int)$log['csId'];
        //                 $coupon_set_money = $coupon_set_tab->where(
        //                     array(
        //                         'csId' => $csId
        //                     )
        //                 )->getField('nprice');
        //                 $wx_coupon_signle += (float)$coupon_set_money;
        //                 $wxpay_money_total += (float)$coupon_set_money;
        //                 $wxpay_money_signle += (float)$coupon_set_money;
        //             }
        //         }
        //     }
        //     $v['salesOrderNum'] = $salesOrderNum_signle;
        //     $v['salesOrderMoney'] = $real_money_signle;
        //     $v['coupon_money_signle'] = $coupon_money_signle;
        //     $v['goods_money_signle'] = $goods_money_signle;
        //     $v['need_money_signle'] = $need_money_signle;
        //     $v['score_money_signle'] = $score_money_signle;
        //     $v['delivery_money_signle'] = $delivery_money_signle;
        //     $v['wxpay_money_signle'] = $wxpay_money_signle;
        //     $v['cash_money_signle'] = $cash_money_signle;

        //     $v['wxpay_money_out_signle'] = $wxpay_money_out_signle;//微信退款金额【包含补差价、售后】
        //     $v['cash_money_out_signle'] = $cash_money_out_signle;//余额退款金额【包含补差价、售后】

        //     $v['wx_cash_recharge_signle'] = $wx_cash_recharge_signle;//微信余额充值金额
        //     $v['wx_set_meal_signle'] = $wx_set_meal_signle;//微信开通会员金额
        //     $v['wx_coupon_signle'] = $wx_coupon_signle;//微信购买优惠券金额

        //     $v['wxpay_money_real_signle'] = bc_math($wxpay_money_signle, $wxpay_money_out_signle, 'bcsub', 2);//微信实际到账金额
        //     $v['cash_money_real_signle'] = bc_math($cash_money_signle, $cash_money_out_signle, 'bcsub', 2);//余额实际到账金额
        //     $v['income'] = bc_math($real_money_signle, $wxpay_money_out_signle, 'bcsub', 2);//收入 减去微信退款
        //     $v['income'] = bc_math($v['income'], $cash_money_out_signle, 'bcsub', 2);//收入 减去余额退款


        //     $income_total += $v['income'];
        //     //以下字段前端用错了,这里临时修改下
        //     $v['wxpay_money_real_signle'] = $wxpay_money_signle;
        //     $v['wxpay_money_signle'] = bc_math($wxpay_money_signle, $wxpay_money_out_signle, 'bcsub', 2);
        //     $v['cash_money_signle'] = bc_math($cash_money_signle, $cash_money_out_signle, 'bcsub', 2);//余额实际到账金额
        //     $v['cash_money_real_signle'] = $cash_money_signle;


        // }
        // unset($v);
        // $arrayPage = arrayPage($reportData, $page, $pageSize);
        // $list = [];
        // $list['list'] = $arrayPage;
        // $list['salesOrderSum'] = $salesOrderSum;
        // $list['salesOrderMoneySum'] = formatAmountNum($salesOrderMoneySum);
        // $list['salesRefundOrderSum'] = $salesRefundOrderSum;
        // $list['salesRefundOrderMoneySum'] = formatAmountNum($salesRefundOrderMoneySum);
        // $list['practicalPriceMoneySum'] = bc_math($salesOrderMoneySum, $salesRefundOrderMoneySum, 'bcsub', 2);
        // $list['salesOrderMoneySum'] = formatAmount($real_money_total);
        // $list['coupon_money_total'] = formatAmount($coupon_money_total);
        // $list['goods_money_total'] = formatAmount($goods_money_total);
        // $list['need_money_total'] = formatAmount($need_money_total);
        // $list['score_money_total'] = formatAmount($score_money_total);
        // $list['delivery_money_total'] = formatAmount($delivery_money_total);
        // $list['cash_money_total'] = formatAmount($cash_money_total);
        // $list['wxpay_money_total'] = formatAmount($wxpay_money_total);
        // $list['wxpay_money_out_total'] = formatAmount($wxpay_money_out_total);//微信退款金额【包含补差价、售后】
        // $list['cash_money_out_total'] = formatAmount($cash_money_out_total);//余额退款金额【包含补差价、售后】

        // $list['wx_cash_recharge_total'] = formatAmount($wx_cash_recharge_total);//微信余额充值总金额
        // $list['wx_set_meal_total'] = formatAmount($wx_set_meal_total);//微信开通会员总金额
        // $list['wx_coupon_total'] = formatAmount($wx_coupon_total);//微信购买优惠券总金额
        // $list['cash_money_real_total'] = bc_math($cash_money_total, $cash_money_out_total, 'bcsub', 2);//余额实际到账总金额
        // $list['wxpay_money_real_total'] = bc_math($wxpay_money_total, $wxpay_money_out_total, 'bcsub', 2);//微信实际到账总金额
        // $list['income_total'] = $income_total;//收入
        if ($export == 1) {
            $this->exportStatOrder($reportData, $list);
            exit();
        }
        return $list;
    }

    /**
     * @param $list
     * @param $reportDate
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出----获取订单统计列表
     */
    public function exportStatOrder($list, $reportDate)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '营业数据';
        $excel_filename = '营业数据' . date('Ymd_His');
//        $sheet_title = array('日期', '下单笔数', '商品总金额', '订单应收金额', '实收金额', '优惠券金额', '积分抵扣金额', '配送费', '余额支付金额', '微信支付金额', '余额退款金额', '微信退款金额', '微信余额充值总金额', '微信开通会员总金额', '微信购买优惠券总金额', '余额实际到账总金额', '微信实际到账总金额');

        $sheet_title = array('日期', '下单笔数', '商品总金额', '配送费', '订单应收金额', '优惠券金额', '积分抵扣金额', '实收金额', '微信退款金额', '收入', '微信开通会员总金额', '余额支付金额', '微信支付金额', '余额退款金额', '微信余额充值总金额', '微信购买优惠券总金额', '余额实际到账总金额', '微信实际到账总金额', '支付宝退款金额', '支付宝开通会员总金额', '支付宝支付金额', '支付宝余额充值金额', '支付宝购买优惠券总金额', '支付宝实际到账总金额');
//        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X');
        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
//            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            if ($i == count($list)) {
                $temp = $reportDate;
                if (!empty($reportDate)) {
                    $temp['reportDate'] = "合计";//日期
                    $temp['salesOrderNum'] = $reportDate['salesOrderSum'];//下单笔数
                    $temp['goods_money_signle'] = $reportDate['goods_money_total'];//商品总金额
                    $temp['need_money_signle'] = $reportDate['need_money_total'];//订单应收金额
                    $temp['salesOrderMoney'] = $reportDate['salesOrderMoneySum'];//实收金额
                    $temp['coupon_money_signle'] = $reportDate['coupon_money_total'];//优惠券金额
                    $temp['score_money_signle'] = $reportDate['score_money_total'];
                    $temp['delivery_money_signle'] = $reportDate['delivery_money_total'];
//                    $temp['cash_money_signle'] = $reportDate['cash_money_total'];
                    $temp['cash_money_signle'] = $reportDate['cash_money_real_total'];
//                    $temp['wxpay_money_signle'] = $reportDate['wxpay_money_total'];
                    $temp['wxpay_money_signle'] = $reportDate['wxpay_money_real_total'];
                    $temp['cash_money_out_signle'] = $reportDate['cash_money_out_total'];
                    $temp['wxpay_money_out_signle'] = $reportDate['wxpay_money_out_total'];
                    $temp['wx_cash_recharge_signle'] = $reportDate['wx_cash_recharge_total'];
                    $temp['wx_set_meal_signle'] = $reportDate['wx_set_meal_total'];
                    $temp['wx_coupon_signle'] = $reportDate['wx_coupon_total'];
//                    $temp['cash_money_real_signle'] = $reportDate['cash_money_real_total'];
                    $temp['cash_money_real_signle'] = $reportDate['cash_money_total'];
//                    $temp['wxpay_money_real_signle'] = $reportDate['wxpay_money_real_total'];
                    $temp['wxpay_money_real_signle'] = $reportDate['wxpay_money_total'];
                    $temp['income'] = $reportDate['income_total'];//收入


                    $temp['alipay_cash_recharge_signle'] = $reportDate['alipay_cash_recharge_total'];
                    $temp['alipay_set_meal_signle'] = $reportDate['alipay_set_meal_total'];
                    $temp['alipay_coupon_signle'] = $reportDate['alipay_coupon_total'];
                    $temp['alipay_money_signle'] = $reportDate['alipay_money_real_total'];
                    $temp['alipay_money_out_signle'] = $reportDate['alipay_money_out_total'];
                    $temp['alipay_money_real_signle'] = $reportDate['alipay_money_total'];


                }
            }
            $letterCount = count($letter);
            //日期', '下单笔数',  '下单金额', '发货金额','退货笔数','退货金额
            for ($j = 0; $j < $letterCount; $j++) {
                switch ($j) {
                    case 0 :
                        //日期
                        $cellvalue = $temp['reportDate'];
                        break;
                    case 1 :
                        //下单笔数
                        $cellvalue = $temp['salesOrderNum'];
                        break;
                    case 2 :
                        //商品总金额
                        $cellvalue = $temp['goods_money_signle'];
                        break;
                    case 3 :
                        //配送费
                        $cellvalue = $temp['delivery_money_signle'];
                        break;
                    case 4 :
                        //订单应收金额
                        $cellvalue = $temp['need_money_signle'];
                        break;
                    case 5 :
                        //优惠券金额
                        $cellvalue = $temp['coupon_money_signle'];
                        break;
                    case 6 :
                        //积分抵扣金额
                        $cellvalue = $temp['score_money_signle'];
                        break;
                    case 7 :
                        //实收金额
                        $cellvalue = $temp['salesOrderMoney'];
                        break;
                    case 8 :
                        //微信退款金额
                        $cellvalue = $temp['wxpay_money_out_signle'];
                        break;
                    case 9 :
                        //收入
                        $cellvalue = $temp['income'];
                        break;
                    case 10 :
                        //微信开通会员金额
                        $cellvalue = $temp['wx_set_meal_signle'];
                        break;
                    case 11 :
                        //余额支付
//                        $cellvalue = $temp['cash_money_signle'];
                        $cellvalue = $temp['cash_money_real_signle'];
                        break;
                    case 12 :
                        //微信支付
//                        $cellvalue = $temp['wxpay_money_signle'];
                        $cellvalue = $temp['wxpay_money_real_signle'];
                        break;
                    case 13 :
                        //余额退款金额
                        $cellvalue = $temp['cash_money_out_signle'];
                        break;
                    case 14 :
                        //微信余额充值总金额
                        $cellvalue = $temp['wx_cash_recharge_signle'];
                        break;
                    case 15 :
                        //微信购买优惠券金额
                        $cellvalue = $temp['wx_coupon_signle'];
                        break;
                    case 16 :
                        //余额实际到账金额
//                        $cellvalue = $temp['cash_money_real_signle'];
                        $cellvalue = $temp['cash_money_signle'];
                        break;
                    case 17 :
                        //微信实际到账金额
//                        $cellvalue = $temp['wxpay_money_real_signle'];
                        $cellvalue = $temp['wxpay_money_signle'];
                        break;

                    case 18 :
                        //支付宝退款金额
                        $cellvalue = $temp['alipay_money_out_signle'];
                        break;
                    case 19 :
                        //支付宝开通会员总金额
                        $cellvalue = $temp['alipay_set_meal_signle'];
                        break;
                    case 20 :
                        //支付宝支付金额
                        $cellvalue = $temp['alipay_money_real_signle'];
                        break;
                    case 21 :
                        //支付宝余额充值金额
                        $cellvalue = $temp['alipay_cash_recharge_signle'];
                        break;
                    case 22 :
                        //支付宝购买优惠券总金额
                        $cellvalue = $temp['alipay_coupon_signle'];
                        break;
                    case 23 :
                        //支付宝实际到账总金额
                        $cellvalue = $temp['alipay_money_signle'];
                        break;



//                        $temp['alipay_cash_recharge_signle'] = $reportDate['alipay_cash_recharge_total'];
//                        $temp['alipay_set_meal_signle'] = $reportDate['alipay_set_meal_total'];
//                        $temp['alipay_coupon_signle'] = $reportDate['alipay_coupon_total'];
//                        $temp['alipay_money_signle'] = $reportDate['alipay_money_real_total'];
//                        $temp['alipay_money_out_signle'] = $reportDate['alipay_money_out_total'];
//                        $temp['alipay_money_real_signle'] = $reportDate['alipay_money_total'];
//
//                        '支付宝退款金额', '支付宝开通会员总金额', '支付宝支付金额', '支付宝余额充值金额', '支付宝购买优惠券总金额', '支付宝实际到账总金额'
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(20);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @param int $profitType
     * @param int $userType
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 获取客户统计列表
     * $profitType 销售毛利状态
     */
    public function getUsersList($startDate, $endDate, $profitType = 0, $userType = 0, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        $where['sor.shopId'] = $shopId;
        if (!empty(I('shopName'))) {//门店名称
            $where['ws.shopName'] = I('shopName');
        }
        if (!empty(I('goodsSn'))) {//商品编码
            $where['wg.goodsSn'] = I('goodsSn');
        }
        $field = "wu.userId,wu.userName,sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

//        $data = M('sales_order_report sor')
//            ->join('left join wst_users wu ON wu.userId = sor.userId')
//            ->join('left join wst_goods wg ON wg.goodsId = sor.goodsId')
//            ->join('left join wst_shops ws ON ws.shopId = sor.shopId')
//            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = sor.goodsCatId1')
//            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = sor.goodsCatId2')
//            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = sor.goodsCatId3')
//            ->where($where)
//            ->field($field)
//            ->group('sor.id')
//            ->select();
        $res = [];
        foreach ($data as $v) {
            $res[$v['userId']][] = $v;
        }
        $arr = [];
        $array = [];
        $practicalSum = 0;//所有商品的实际价格
        $order_id_arr = array_unique(array_column($data, 'orderId'));
        $orderSum = count($order_id_arr);//所有订单数量
        $refundOrderSum = 0;//所有退款订单数量
        $refundGoodsSum = 0;//所有退款商品数量
        $marketSum = 0;//所有销售数量
        $goodsCostPriceSum = 0;//所有进货价--成本价
        $profitSum = 0;//所有毛利
        $salePriceCountSum = 0;//所有销售金额
        $orders_tab = M('orders');
        $order_list = $orders_tab->where(array('orderId' => array('IN', $order_id_arr)))->select();
        $order_list_map = [];
        foreach ($order_list as $order_list_row) {
            $order_list_map[$order_list_row['orderId']] = $order_list_row;
        }
        $returnPriceCountSum = 0;//所有退货金额
        $coupon_money_total = 0;//优惠券金额总计
        $goods_money_total = 0;//商品金额统计
        $need_money_total = 0;//订单应收金额
        $score_money_total = 0;//订单积分抵扣金额
        $delivery_money_total = 0;//运费金额
        $cash_money_total = 0;//余额支付金额
        $wxpay_money_total = 0;//微信支付金额
//        $coupon_model = M('coupons');
        foreach ($order_list as $item) {
//            if ($item['couponId'] > 0) {
//                $coupon_info = $coupon_model->where(array(
//                    'couponId' => $item['couponId']
//                ))->find();
//                if ($coupon_info) {
//                    $coupon_money_total += (float)$coupon_info['couponMoney'];
//                }
//            }
            $coupon_money_total += (float)$item['coupon_use_money'];
            $coupon_money_total += (float)$item['delivery_coupon_use_money'];

            $salePriceCountSum += (float)$item['realTotalMoney'];
            $goods_money_total += (float)$item['totalMoney'];
            $need_money_total += (float)$item['needPay'];
            $score_money_total += (float)$item['scoreMoney'];
            $delivery_money_total += (float)$item['deliverMoney'];
            if ($item['payFrom'] == 2) {
                //微信支付
                $wxpay_money_total += (float)$item['realTotalMoney'];
            } elseif ($item['payFrom'] == 3) {
                //余额支付
                $cash_money_total += (float)$item['realTotalMoney'];
            }
        }
        $goodsNumSum = 0;//所有商品数量
        $returnCostPriceSum = 0;//所有退款成本价
        $rank = 0;//综合排名
        foreach ($res as $v) {
            $users_order_id_arr = array_unique(array_column($v, 'orderId'));
//            $saleCount = count($users_order_id_arr);
            $orderNum = count($users_order_id_arr);
            $saleCount = 0;//销售笔数
            $salePriceCount = 0;//销售金额
//            $users_order_list = $orders_tab->where(array('orderId' => array('IN', $users_order_id_arr)))->select();
            //新加字段
            $users_order_list = [];
            foreach ($users_order_id_arr as $users_order_id) {
                $order_list_map_row = $order_list_map[$users_order_id];
                if (empty($order_list_map_row)) {
                    continue;
                }
                $users_order_list[] = $order_list_map_row;
            }
            $coupon_money_signle = 0;//优惠券金额
            $goods_money_signle = 0;//商品金额
            $need_money_signle = 0;//订单应收金额
            $score_money_signle = 0;//积分抵扣金额
            $delivery_money_signle = 0;//配送费金额
            $cash_money_signle = 0;//余额支付
            $wxpay_money_signle = 0;//微信支付金额
            foreach ($users_order_list as $item) {
                $salePriceCount += $item['realTotalMoney'];
//                if ($item['couponId'] > 0) {
//                    $coupon_info = $coupon_model->where(array(
//                        'couponId' => $item['couponId']
//                    ))->find();
//                    if ($coupon_info) {
//                        $coupon_money_signle += (float)$coupon_info['couponMoney'];
//                    }
//                }
                $coupon_money_signle += (float)$item['coupon_use_money'];
                $coupon_money_signle += (float)$item['delivery_coupon_use_money'];

                $goods_money_signle += (float)$item['totalMoney'];
                $need_money_signle += (float)$item['needPay'];
                $score_money_signle += (float)$item['scoreMoney'];
                $delivery_money_signle += (float)$item['deliverMoney'];
                if ($item['payFrom'] == 2) {
                    //微信支付
                    $cash_money_signle += (float)$item['realTotalMoney'];
                } elseif ($item['payFrom'] == 3) {
                    //余额支付
                    $wxpay_money_signle += (float)$item['realTotalMoney'];
                }
            }
            $returnCount = 0;//退货笔数
            $returnPriceCount = 0;//退货金额
            $goodsPriceNum = 0;//商品实际金额
            $marketNum = 0;//销售数量
            $goodsCostPrice = 0;//进货价--成本价
            $profit = 0;//毛利
            $refundGoodsCount = 0;//退货商品数量
            //$orderNum = 0;//订单数量
            $goodsNums = 0;//商品数量
            $saleGoodsCountNum = 0;//销售商品数量
            $returnCostPriceNum = 0;//退款成本价
            $saleCountArr = array();//用于bug修复
            foreach ($v as $val) {
                $userName = $val['userName'];//客户名称
                $userId = $val['userId'];//客户Id
                if ($val['is_return_goods'] == 1) {
                    $returnCount += 1;
                    $returnPriceCount += $val['refundFee'];
                    if ($val['is_cancel_order'] == 1) {
                        //$cancel_info = $orders_tab->where(array('orderId' => $val['orderId']))->find();
                        $cancel_info = $order_list_map[$val['orderId']];
                        $returnPriceCount += $cancel_info['deliverMoney'];
                    }
                    $refundGoodsCountNum = $val['goodsNums'];
                }
                $saleCountArr[$val['orderId']] = $val['orderId'];
                //$salePriceCount += $val['goodsPaidPrice'];
                $saleGoodsCount = $val['goodsNums'];
                $saleGoodsCountNum += $saleGoodsCount;
                if ($profitType == 1) {
                    //获取销售毛利列表
                    $goodsNums += $val['goodsNums'];
                    $marketNum += $val['goodsNums'];
                    //$orderNum += 1;
//                    $saleCount += $val['goodsNums'];
                    $goodsPrice = $saleGoodsCount * $val['goodsPrice'];
                    $goodsPriceNum += $goodsPrice;
                    $goodsCostPriceNum = $val['goodsCostPrice'] * $saleGoodsCount;//销售商品成本价
                    $goodsCostPrice += $goodsCostPriceNum;
                    if ($val['is_return_goods'] == 1) {
                        $returnCostPriceNum += $val['goodsCostPrice'] * $refundGoodsCountNum;//商品退款成本价
                        $refundGoodsCount += $val['goodsNums'];
                    }
                    $profitNum = $goodsPrice - $goodsCostPriceNum;//毛利
                    $profit += $profitNum;
                }
                $reportDate = $val['reportDate'];//发货日期
            }
            $rank += 1;//综合排名
            //销售成本	退货成本	实际成本	毛利	毛利率
            $arr['userId'] = $userId;//客户Id
            $arr['userName'] = $userName;//客户名称
            $arr['returnCount'] = $returnCount;
            $arr['returnPriceCount'] = formatAmountNum($returnPriceCount);//退货金额
            $arr['saleCount'] = $saleCount;//订单数量
            if ($profitType != 1) {
                $arr['saleCount'] = count($saleCountArr);//订单数量
            }
            $arr['salePriceCount'] = formatAmountNum($salePriceCount);//销售金额
            $arr['reportDate'] = $reportDate;//发货日期
            $arr['rank'] = $rank;//综合排名
            $arr['coupon_money_signle'] = formatAmountNum($coupon_money_signle);
            $arr['goods_money_signle'] = formatAmountNum($goods_money_signle);
            $arr['need_money_signle'] = formatAmountNum($need_money_signle);
            $arr['score_money_signle'] = formatAmountNum($score_money_signle);
            $arr['delivery_money_signle'] = formatAmountNum($delivery_money_signle);
            $arr['cash_money_signle'] = formatAmountNum($cash_money_signle);
            $arr['wxpay_money_signle'] = formatAmountNum($wxpay_money_signle);
            //毛利--根据客户分类---然后再根据商品分类
            $goodsNumSum += $goodsNums;//所有商品数量
            $returnPriceCountSum += $returnPriceCount;//所有退货金额
            $profitSum += $profit;//所有毛利
            $goodsCostPriceSum += $goodsCostPrice;//所有商品成本价
            $practicalSum += $goodsPriceNum;//所有商品的实际价格
            //$salePriceCountSum += $salePriceCount;//所有销售金额
            $marketSum += $marketNum;//所有销售数量
            $refundGoodsSum += $refundGoodsCount;//所有退款商品数量
            $refundOrderSum += $returnCount;//所有退款订单数量
            $returnCostPriceSum += $returnCostPriceNum;//所有退款成本价
            //$orderSum += $saleCount;
            if ($profitType == 1) {
                //获取销售毛利列表
                $profitPercent = round($profit / $goodsPriceNum * 100) . "％";//毛利率
                $arr['orderNum'] = $orderNum;//订单数量
                $arr['goodsNums'] = $goodsNums;//商品数量
                $arr['marketNum'] = $marketNum;//销售数量
                $arr['refundOrderCount'] = $returnCount;//退款订单数量
                $arr['refundGoodsCount'] = $refundGoodsCount;//退款商品数量
                $arr['profitSum'] = formatAmountNum($profit);//毛利
                $arr['profitPercent'] = $profitPercent;//毛利率
                $arr['returnPriceCount'] = formatAmountNum($returnPriceCount);//退货金额
                $arr['goodsCostPrice'] = formatAmountNum($goodsCostPrice);//商品成本价
                $arr['goodsPriceNum'] = formatAmountNum(($goodsCostPrice - $returnCostPriceNum));//商品的实际成本价格
                $arr['salePriceCount'] = $salePriceCount;//销售金额
                $arr['returnCostPriceNum'] = formatAmountNum($returnCostPriceNum);//退款成本价
                $arr['practicalPrice'] = bc_math($arr['salePriceCount'], $arr['returnPriceCount'], 'bcsub', 2);//实际金额=销售金额-退货金额
                $arr['practicalCostPrice'] = bc_math($arr['goodsCostPrice'], $arr['returnCostPriceNum'], 'bcsub', 2);//实际成本
                $arr['profitSum'] = bc_math($arr['practicalPrice'], $arr['practicalCostPrice'], 'bcsub', 2);//毛利=实际金额-实际成本
                $arr['profitPercent'] = (bc_math($arr['profitSum'], $arr['practicalPrice'], 'bcdiv', 4) * 100) . '%';//毛利率=毛利/实际金额x100%
            }
            $array[] = $arr;
        }
        $arrayPage = arrayPage($array, $page, $pageSize);
        $list = [];
        $list['list'] = $arrayPage;
        $list['orderSum'] = $orderSum;//订单数量
        $list['refundOrderSum'] = $refundOrderSum;//所有退款订单数量
        $list['salesOrderMoneySum'] = formatAmountNum($salePriceCountSum);//销售金额
        $list['returnPriceCountSum'] = formatAmountNum($returnPriceCountSum);//退货金额
        $list['coupon_money_total'] = formatAmountNum($coupon_money_total);
        $list['goods_money_total'] = formatAmountNum($goods_money_total);
        $list['need_money_total'] = formatAmountNum($need_money_total);
        $list['score_money_total'] = formatAmountNum($score_money_total);
        $list['delivery_money_total'] = formatAmountNum($delivery_money_total);
        $list['wxpay_money_total'] = formatAmountNum($wxpay_money_total);
        $list['cash_money_total'] = formatAmountNum($cash_money_total);
        //获取销售毛利列表
        if ($profitType == 1 && $userType == 0) {
            $sum = [];
            $sum['orderSum'] = $orderSum;//订单数量
            $sum['refundOrderSum'] = $refundOrderSum;//所有退款订单数量
            $sum['salesOrderMoneySum'] = formatAmountNum($salePriceCountSum);//销售金额
            $sum['returnPriceCountSum'] = formatAmountNum($returnPriceCountSum);//退货金额
            $sum['goodsCostPriceSum'] = formatAmountNum($goodsCostPriceSum);//销售成本
            $sum['returnCostPriceSum'] = formatAmountNum($returnCostPriceSum);//所有退款成本价
            $sum['goodsRealityCostPriceSum'] = formatAmountNum($goodsCostPriceSum);//实际成本
            $sum['refundGoodsSum'] = $refundGoodsSum;//退货数量
            $sum['goodsNumSum'] = $goodsNumSum;//实际数量
            $sum['practicalSum'] = bc_math($sum['salesOrderMoneySum'], $sum['returnPriceCountSum'], 'bcsub', 2);//实际金额=销售金额-退货金额
            $sum['practicalCostPriceSum'] = bc_math($sum['goodsRealityCostPriceSum'], $sum['returnCostPriceSum'], 'bcsub', 2);//实际成本
            $sum['profitSum'] = bc_math($sum['practicalSum'], $sum['practicalCostPriceSum'], 'bcsub', 2);//毛利=实际金额-实际成本
            $list['sum'] = $sum;
        }
        if ($export == 1) {
            $this->exportUsersList($array, $list, $profitType, $userType);
            exit();
        }
        return $list;
    }

    /**
     * @param $list
     * @param $reportDate
     * @param $profitType
     * @param $userType
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出---获取客户统计列表
     */
    public function exportUsersList($list, $reportDate, $profitType, $userType)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = '客户统计报表';
        $excel_filename = '客户统计报表' . date('Ymd_His');
        $sheet_title = array('客户名', '订货笔数', '退货笔数', '订单金额', '退货金额', '综合排名');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F');
        if ($profitType == 1 && $userType == 0) {//毛利统计---按客户统计
            $title = '毛利列表';
            $excel_filename = '毛利列表' . date('Ymd_His');
            $sheet_title = array('客户名', '订单数量', '退单数量', '销售金额', '退货金额', '销售成本', '退货成本', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        } else if ($profitType == 1 && $userType == 1) {//客户毛利
            $title = '客户毛利';
            $excel_filename = '客户毛利' . date('Ymd_His');
            $sheet_title = array('发货日期', '客户名称', '销售金额', '销售成本', '退货金额', '退货成本', '实际金额', '毛利', '毛利率');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        }

        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            if ($i == count($list)) {
                $temp = $reportDate;
                if (!empty($reportDate)) {
                    if ($profitType == 1 && $userType == 0) {//毛利统计---按客户统计
                        $temp['userName'] = "合计";//客户名
                        $temp['orderNum'] = $reportDate['sum']['orderSum'];//订单数量
                        $temp['refundOrderCount'] = $reportDate['sum']['refundOrderSum'];//退货数量
                        $temp['salePriceCount'] = $reportDate['sum']['practicalSum'];//销售金额
                        $temp['returnPriceCount'] = $reportDate['sum']['returnPriceCountSum'];//退货金额
                        $temp['goodsCostPrice'] = $reportDate['sum']['goodsCostPriceSum'];//销售成本
                        $temp['returnCostPriceNum'] = $reportDate['sum']['returnCostPriceSum'];//退货成本
                        $temp['profitSum'] = $reportDate['sum']['profitSum'];//毛利
                    } else if ($profitType == 0 && $userType == 0) {
                        $temp['userName'] = "合计";//客户名
                        $temp['saleCount'] = $reportDate['orderSum'];//订货笔数
                        $temp['returnCount'] = $reportDate['refundOrderSum'];//退货笔数
                        $temp['salePriceCount'] = $reportDate['salesOrderMoneySum'];//订单金额
                        $temp['returnPriceCount'] = $reportDate['returnPriceCountSum'];//退货金额
                    }
                }
            }
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                if ($profitType == 1 && $userType == 0) {//毛利统计---按客户统计
                    switch ($j) {
                        case 0 :
                            //客户名
                            $cellvalue = $temp['userName'];
                            break;
                        case 1 :
                            //订单数量
                            $cellvalue = $temp['orderNum'];
                            break;
                        case 2 :
                            //退货数量
                            $cellvalue = $temp['refundOrderCount'];
                            break;
                        case 3 :
                            //销售金额
                            $cellvalue = $temp['salePriceCount'];
                            break;
                        case 4 :
                            //退货金额
                            $cellvalue = $temp['returnPriceCount'];
                            break;
                        case 5 :
                            //销售成本
                            $cellvalue = $temp['goodsCostPrice'];
                            break;
                        case 6 :
                            //退货成本
                            $cellvalue = $temp['returnCostPriceNum'];
                            break;
                        case 7 :
                            //毛利
                            $cellvalue = $temp['profitSum'];
                            break;
                        case 8 :
                            //毛利率
                            $cellvalue = $temp['profitPercent'];
                            break;
                    }
                } else if ($profitType == 1 && $userType == 1) {//客户毛利
                    switch ($j) {
                        case 0 :
                            //发货日期
                            $cellvalue = $temp['reportDate'];
                            break;
                        case 1 :
                            //客户名称
                            $cellvalue = $temp['userName'];
                            break;
                        case 2 :
                            //销售金额
                            $cellvalue = $temp['salePriceCount'];
                            break;
                        case 3 :
                            //销售成本
                            $cellvalue = $temp['goodsCostPrice'];
                            break;
                        case 4 :
                            //退货金额
                            $cellvalue = $temp['returnPriceCount'];
                            break;
                        case 5 :
                            //退货成本
                            $cellvalue = $temp['returnCostPriceNum'];
                            break;
                        case 6 :
                            //实际金额
                            $cellvalue = $temp['goodsPriceNum'];
                            break;
                        case 7 :
                            //毛利
                            $cellvalue = $temp['profitSum'];
                            break;
                        case 8 :
                            //毛利率
                            $cellvalue = $temp['profitPercent'];
                            break;
                    }
                } else {
                    switch ($j) {
                        case 0 :
                            //客户名
                            $cellvalue = $temp['userName'];
                            break;
                        case 1 :
                            //订货笔数
                            $cellvalue = $temp['saleCount'];
                            break;
                        case 2 :
                            //退货笔数
                            $cellvalue = $temp['returnCount'];
                            break;
                        case 3 :
                            //订单金额
                            $cellvalue = $temp['salePriceCount'];
                            break;
                        case 4 :
                            //退货金额
                            $cellvalue = $temp['returnPriceCount'];
                            break;
                        case 5 :
                            //综合排名
                            $cellvalue = $temp['rank'];
                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $params
     * @return mixed
     * 毛利--根据客户分类---然后再根据商品分类
     */
    public function getProfitInfo($params)
    {
        $saleCount = 0;//销售笔数
        $salePriceCount = 0;//销售金额
        $returnCount = 0;//退货笔数
        $returnPriceCount = 0;//退货金额
        $goodsPriceNum = 0;//商品实际金额
        $marketNum = 0;//销售数量
        $goodsCostPrice = 0;//进货价--成本价
        $profit = 0;//毛利
        $refundGoodsCount = 0;//退货商品数量
        $orderNum = 0;//订单数量
        $goodsNums = 0;//商品数量
        foreach ($params as $val) {
            $userName = $val['userName'];
            if ($val['isRefund'] == 1) {//是否退款[1:是|-1:否]
                $returnCount += 1;
                $returnPriceCount += $val['refundFee'];
                $refundGoodsCount += $val['goodsNums'];
            } else {
                $saleCount += 1;
                $salePriceCount += $val['goodsPaidPrice'];
            }
            //获取销售毛利列表
            $goodsName = $val['goodsName'];
            $goodsSn = $val['goodsSn'];
            $goodsDesc = $val['goodsSpec'];
            $goodsCatName1 = $val['goodsCatName1'];
            $goodsCatName2 = $val['goodsCatName2'];
            $goodsCatName3 = $val['goodsCatName3'];
            $goodsNums += $val['goodsNums'];
            $orderNum += 1;
            $goodsPriceNum += $val['goodsNums'] * $val['goodsPrice'];
            $goodsCostPrice += $val['goodsCostPrice'] * $val['goodsNums'];//当前商品成本价
            $profit += $goodsPriceNum - $goodsCostPrice;//毛利
        }
        $arr['userName'] = $userName;
        $arr['returnCount'] = $returnCount;
        $arr['returnPriceCount'] = $returnPriceCount;
        $arr['saleCount'] = $saleCount;
        $arr['salePriceCount'] = $salePriceCount;
        //获取销售毛利列表
        $profitPercent = round($profit / $salePriceCount * 100) . "％";//毛利率
        $arr['goodsName'] = $goodsName;
        $arr['goodsSn'] = $goodsSn;
        $arr['goodsSpec'] = $goodsDesc;
        $arr['goodsCatName1'] = $goodsCatName1;
        $arr['goodsCatName2'] = $goodsCatName2;
        $arr['goodsCatName3'] = $goodsCatName3;
        $arr['orderNum'] = $orderNum;//订单数量
        $arr['goodsNums'] = $goodsNums;//商品数量
        $arr['marketNum'] = $marketNum;//销售数量
        $arr['refundOrderCount'] = $returnCount;//退款订单数量
        $arr['refundGoodsCount'] = $refundGoodsCount;//退款商品数量
        $arr['profitSum'] = formatAmountNum($profit);//毛利
        $arr['profitPercent'] = $profitPercent;//毛利率
        $arr['returnPriceCount'] = $returnPriceCount;//退货金额
        $arr['goodsCostPrice'] = formatAmountNum($goodsCostPrice);//商品成本价
        $arr['goodsPriceNum'] = formatAmountNum($goodsPriceNum);//商品的实际价格
        $arr['salePriceCount'] = $salePriceCount;//销售金额
        return $arr;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array|mixed
     * @throws \PHPExcel_Exception
     * 获取销售毛利列表
     */
    public function profitList($startDate, $endDate, $shopId)
    {
        $type = (int)I('statType', 1);//1:按商品统计|2:按商品分类统计|3:按客户统计
        $res = array();
        if ($type == 1) {//按商品统计
            $res = $this->commoditySaleList($startDate, $endDate, $type = 1, $profitType = 1, $shopId);
        }
        if ($type == 2) {//按商品分类统计
            $res = $this->commoditySaleList($startDate, $endDate, $type = 2, $profitType = 1, $shopId);
        }
        if ($type == 3) {//按客户统计
            $res = $this->getUsersList($startDate, $endDate, $profitType = 1, $userType = 0, $shopId);
        }
        return $res;
    }

    /**
     * @param $goodsId
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 销售毛利列表--商品列表---获取商品详情
     */
    public function commodityProfitList($goodsId, $shopId)
    {
        $res = $this->commoditySalesUserDetail($goodsId, $goodsCatId3 = 0, $profitType = 1, $shopId);
        return $res;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * 销售毛利---图表模式
     */
    public function profitStatData($startDate, $endDate, $shopId)
    {
        $res = $this->CommoditySaleData($startDate, $endDate, $profitType = 1, $shopId);
        return $res;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 客户毛利
     */
    public function userStatic($startDate, $endDate, $shopId)
    {
        $res = $this->getUsersList($startDate, $endDate, $profitType = 1, $userType = 1, $shopId);
        return $res;
    }

    /**
     * @param $userId
     * @param $shopId
     * @return mixed
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 客户毛利----详情
     */
    public function userProfitStatList($userId, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);
        $where = [];
        $where['sor.userId'] = $userId;
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        $where['sor.shopId'] = $shopId;
        $field = "sor.*,wg.goodsName";
        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

//        $data = M('sales_order_report sor')
//            ->join('left join wst_users wu ON wu.userId = sor.userId')
//            ->join('left join wst_goods wg ON wg.goodsId = sor.goodsId')
//            ->where($where)
//            ->field($field)
//            ->group('sor.id')
//            ->select();
        $res = [];
        foreach ($data as $v) {
            $res[$v['goodsId']][] = $v;
        }
        $arr = [];
        $array = [];
        foreach ($res as $v) {
            $marketNum = 0;//销售数量
            $salePriceCount = 0;//销售金额
            $saleCostPrice = 0;//销售成本
            $returnPriceCount = 0;//退货价
            $returnCostPriceNum = 0;//退货成本
            $profit = 0;//毛利
            $goodsPriceNum = 0;//商品实际金额
            $goodsCostPrice = 0;//进货价--成本价
            $refundGoodsCount = 0;//退货商品数量
            $goodsName = '';//商品名称
            foreach ($v as $val) {
                if ($val['is_return_goods'] == 1) {//是否退款[1:是|-1:否]
                    $returnPriceCount += $val['refundFee'];
                    $refundGoodsCount += $val['goodsNums'];
                    $returnCostPriceNum += $val['goodsCostPrice'] * $refundGoodsCount;//商品退款成本价
                }
                $salePriceCount += $val['goodsPaidPrice'];
                $goodsName = $val['goodsName'];
                $marketNum += $val['goodsNums'];
                //获取销售毛利列表
                $goodsPrice = $val['goodsNums'] * $val['goodsPrice'];
                $goodsPriceNum += $goodsPrice;
                $goodsCost = $val['goodsCostPrice'] * $val['goodsNums'];//当前商品成本价
                $goodsCostPrice += $goodsCost;
                $saleCostPrice += $val['goodsCostPrice'] * $marketNum;//销售成本
                $profitNum = $goodsPrice - $goodsCost;//毛利
                $profit += $profitNum;
            }
            $arr['goodsName'] = $goodsName;//商品名称
            $arr['marketNum'] = $marketNum;//销售数量
            $arr['salePriceCount'] = formatAmountNum($salePriceCount);//销售价
            $arr['saleCostPrice'] = formatAmountNum($goodsCostPrice);//销售成本
            $arr['goodsCostPrice'] = formatAmountNum($goodsCostPrice);//商品成本价
            $arr['returnPriceCount'] = formatAmountNum($returnPriceCount);//退货价
            $arr['returnCostPriceNum'] = formatAmountNum($returnCostPriceNum);//退货成本
            $practicalPrice = bc_math($arr['salePriceCount'], $arr['returnPriceCount'], 'bcsub', 2);//实际金额=销售金额-退货金额
            $practicalCostPrice = bc_math($arr['goodsCostPrice'], $arr['returnCostPriceNum'], 'bcsub', 2);//实际成本
            $arr['profit'] = bc_math($practicalPrice, $practicalCostPrice, 'bcsub', 2);//毛利=实际金额-实际成本
            $arr['profitPercent'] = (bc_math($arr['profit'], $practicalPrice, 'bcdiv', 4) * 100) . '%';//毛利率=毛利/实际金额x100%
            $array[] = $arr;
        }
        $arrayPage = arrayPage($array, $page, $pageSize);
        //导出
        if ($export == 1) {
            $this->exportUserProfitStat($array);
            exit();
        }
        return $arrayPage;
    }

    /**
     * @param $list
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出--客户毛利----详情
     */
    public function exportUserProfitStat($list)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = "客户毛利商品信息";
        $excel_filename = '客户毛利商品信息_' . date('Ymd_His');
        $sheet_title = array('商品名称', '销售数量', '销售价', '销售成本', '商品成本价', '退货价', '退货成本', '毛利', '毛利率');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                switch ($j) {
                    case 0 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 1 :
                        //销售数量
                        $cellvalue = $temp['marketNum'];
                        break;
                    case 2 :
                        //销售价
                        $cellvalue = $temp['salePriceCount'];
                        break;
                    case 3 :
                        //销售成本
                        $cellvalue = $temp['saleCostPrice'];
                        break;
                    case 4 :
                        //商品成本价
                        $cellvalue = $temp['goodsCostPrice'];
                        break;
                    case 5 :
                        //退货价
                        $cellvalue = $temp['returnPriceCount'];
                        break;
                    case 6 :
                        //退货成本
                        $cellvalue = $temp['returnCostPriceNum'];
                        break;
                    case 7 :
                        //毛利
                        $cellvalue = $temp['profit'];
                        break;
                    case 8 :
                        //毛利率
                        $cellvalue = $temp['profitPercent'];
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
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 采购汇总
     */
    public function purchaseStatDetail($startDate, $endDate, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $type = (int)I('purchaseType', 1);//1:按明细分类|2:按商品分类|3:按供应商分类
        $export = I('export', 0);//1:导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = 1;//是否采购[1:是|-1:否]
        $where['sor.shopId'] = $shopId;
        if (!empty(I('number'))) {//采购单号
            $where['wjp.number'] = I('number');
        }
        $field = "wjp.number,sor.*,wg.goodsName,wjp.createTime,wg.goodsSn,wg.goodsDetail,wgc1.catname as goodsCatName1,wgc2.catname as goodsCatName2,wgc3.catname as goodsCatName3,wjs.account,wjr.supplierId";
        $data = M('sales_order_report sor')
            ->join('left join wst_jxc_goods wg ON wg.goodsId = sor.goodsId')
            ->join('left join wst_jxc_goods_cat wgc1 ON wgc1.catId = sor.goodsCatId1')
            ->join('left join wst_jxc_goods_cat wgc2 ON wgc2.catId = sor.goodsCatId2')
            ->join('left join wst_jxc_goods_cat wgc3 ON wgc3.catId = sor.goodsCatId3')
            ->join('left join wst_jxc_purchase_order wjp ON wjp.otpId = sor.otpId')//采购单主表
            ->join('left join wst_jxc_reinsurance_policy wjr ON wjr.otpId = wjp.otpId')//分单表
            ->join('left join wst_jxc_supplier_config wjs ON wjs.supplierId = wjr.supplierId')//进销存供应商配置表
            ->where($where)
            ->field($field)
            ->group('sor.id')
            ->select();
        $where = [];
        $where['wor.reportDate'] = ['between', [$startDate, $endDate]];
        $fieldInfo = "sum(wor.procureOrderNum) as procureOrderNum,sum(wor.procureOrderMoney) as procureOrderMoney";
        $reportModule = new ReportModule();
        $where['wor.shopId'] = $shopId;
        $reportInfo = $reportModule->getOrderReportList($where, $fieldInfo);
//        $reportInfo = M('order_report sor')->where($where)->field($fieldInfo)->select();
        $res = [];
        $rest = [];
        foreach ($data as $v) {
            if ($type == 3) {//1:按明细分类|2:按商品分类|3:按供应商分类
                $rest[$v['supplierId']][] = $v;
            } else {
                $res[$v['goodsId']][] = $v;
            }
        }
        if ($type == 3) {//3:按供应商分类并且将商品进行聚合
            $res = [];
            foreach ($rest as $v) {
                $goodsList = [];
                foreach ($v as $val) {
                    $goodsList[$val['goodsId']][] = $val;
                }
                $res[] = $goodsList;
            }
        }

        if ($type == 2) {
            $arr = [];
            $data = [];
            foreach ($res as $v) {
                //供应商	商品	描述	单位	入库均价	入库数量	退货均价	退货数量	小计
                $goodsCostPrice = 0;//进货价
                $goodsNums = 0;//商品数量
                $goodsPaidPrice = 0;//商品实付金额【包含商品数量】
                foreach ($v as $val) {
                    $goodsName = $val['goodsName'];//商品
                    $goodsSn = $val['goodsSn'];//商品编码
                    $goodsDetail = $val['goodsDetail'];//描述
                    $goodsCatName1 = $val['goodsCatName1'];//分类
                    $goodsCatName2 = $val['goodsCatName2'];
                    $goodsCatName3 = $val['goodsCatName3'];
                    $goodsCostPrice += $val['goodsCostPrice'];
                    $goodsNums += $val['goodsNums'];
                    $goodsPaidPrice += $val['goodsPaidPrice'];
                }
                $arr['goodsName'] = $goodsName;
                $arr['goodsSn'] = $goodsSn;
                $arr['goodsDetail'] = $goodsDetail;
                $arr['goodsCatName1'] = $goodsCatName1;
                $arr['goodsCatName2'] = $goodsCatName2;
                $arr['goodsCatName3'] = $goodsCatName3;
                $arr['goodsCostPrice'] = formatAmountNum($goodsCostPrice);
                $arr['goodsNums'] = $goodsNums;
                $arr['goodsPaidPrice'] = formatAmountNum($goodsPaidPrice);
                $data[] = $arr;
            }
        } elseif ($type == 3) {
            $arr = [];
            $data = [];
            $goodsIds = [];
            foreach ($res as $v) {//供应商列表循环
                foreach ($v as $val) {//商品列表循环
                    //供应商	商品	描述	单位	入库均价	入库数量	退货均价	退货数量	小计
                    $goodsCostPrice = 0;//进货价
                    $goodsNums = 0;//商品数量
                    $goodsPaidPrice = 0;//商品实付金额【包含商品数量】
                    foreach ($val as $value) {//聚合商品循环
                        $goodsIds[] = $value['goodsId'];
                        $account = $value['account'];//供应商
                        if (empty($value['supplierId'])) {
                            $account = "总仓";
                        }
                        $shopName = $value['shopName'];//店铺名称
                        $goodsName = $value['goodsName'];//商品
                        $goodsSn = $value['goodsSn'];//商品编码
                        $goodsDetail = $value['goodsDetail'];//描述
                        $goodsCatName1 = $value['goodsCatName1'];//分类
                        $goodsCatName2 = $value['goodsCatName2'];
                        $goodsCatName3 = $value['goodsCatName3'];
                        $goodsCostPrice += $value['goodsCostPrice'];
                        $goodsNums += $value['goodsNums'];
                        $goodsPaidPrice += $value['goodsPaidPrice'];
                    }
                    $arr['supplierName'] = $account;
                    $arr['shopName'] = $shopName;
                    $arr['goodsName'] = $goodsName;
                    $arr['goodsSn'] = $goodsSn;
                    $arr['goodsDetail'] = $goodsDetail;
                    $arr['goodsCatName1'] = $goodsCatName1;
                    $arr['goodsCatName2'] = $goodsCatName2;
                    $arr['goodsCatName3'] = $goodsCatName3;
                    $arr['goodsCostPrice'] = formatAmountNum($goodsCostPrice);
                    $arr['goodsNums'] = $goodsNums;
                    $arr['goodsPaidPrice'] = formatAmountNum($goodsPaidPrice);
                    $data[] = $arr;
                }
            }
            $res = array_unique($goodsIds);
        }
        $arrayPage = arrayPage($data, $page, $pageSize);
        $goodsSum = count($res);
        $list = [];
        $list['list'] = $arrayPage;
        $list['procureOrderNum'] = (float)$reportInfo[0]['procureOrderNum'];//采购单数量
        $list['goodsSum'] = $goodsSum;//采购商品种类数量
        $list['procureOrderMoney'] = formatAmountNum($reportInfo[0]['procureOrderMoney']);//采购总金额（已入库）
        //导出
        if ($export == 1) {
            $this->exportPurchaseStat($data, $type);
            exit();
        }
        return $list;
    }

    /**
     * @param $list
     * @param $type
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出---采购汇总
     */
    public function exportPurchaseStat($list, $type)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        if ($type == 1) {
            $title = "按明细采购汇总";
            $excel_filename = '按明细采购汇总_' . date('Ymd_His');
            $sheet_title = array('采购单号', '采购时间', '商品名称', '单价', '收货/退货数量', '收货/退货时间', '一级分类', '二级分类', '三级分类', '小计');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J');
        } elseif ($type == 2) {
            $title = "按商品采购汇总";
            $excel_filename = '按商品采购汇总_' . date('Ymd_His');
            $sheet_title = array('商品名称', '商品编码', '入库均价', '入库数量', '一级分类', '二级分类', '三级分类', '小计');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H');
        } elseif ($type == 3) {
            $title = "按供应商采购汇总";
            $excel_filename = '按供应商采购汇总_' . date('Ymd_His');
            $sheet_title = array('供应商名称', '商品名称', '商品编码', '入库均价', '入库数量', '一级分类', '二级分类', '三级分类', '小计');
            $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        }

        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                if ($type == 1) {
                    switch ($j) {
                        case 0 :
                            //采购单号
                            $cellvalue = $temp['number'];
                            break;
                        case 1 :
                            //采购时间
                            $cellvalue = $temp['createTime'];
                            break;
                        case 2 :
                            //商品名称
                            $cellvalue = $temp['goodsName'];
                            break;
                        case 3 :
                            //单价
                            $cellvalue = $temp['goodsPrice'];
                            break;
                        case 4 :
                            //收货/退货数量
                            $cellvalue = $temp['goodsNums'];
                            break;
                        case 5 :
                            //收货/退货时间
                            $cellvalue = $temp['reportDate'];
                            break;
                        case 6 :
                            //一级分类
                            $cellvalue = $temp['goodsCatName1'];
                            break;
                        case 7 :
                            //二级分类
                            $cellvalue = $temp['goodsCatName2'];
                            break;
                        case 8 :
                            //三级分类
                            $cellvalue = $temp['goodsCatName3'];
                            break;
                        case 9 :
                            //小计
                            $cellvalue = $temp['goodsPaidPrice'];
                            break;
                    }
                } elseif ($type == 2) {
                    switch ($j) {
                        case 0 :
                            //商品名称
                            $cellvalue = $temp['goodsName'];
                            break;
                        case 1 :
                            //商品编码
                            $cellvalue = $temp['goodsSn'];
                            break;
                        case 2 :
                            //入库均价
                            $cellvalue = $temp['goodsCostPrice'];
                            break;
                        case 3 :
                            //入库数量
                            $cellvalue = $temp['goodsNums'];
                            break;
                        case 4 :
                            //一级分类
                            $cellvalue = $temp['goodsCatName1'];
                            break;
                        case 5 :
                            //二级分类
                            $cellvalue = $temp['goodsCatName2'];
                            break;
                        case 6 :
                            //三级分类
                            $cellvalue = $temp['goodsCatName3'];
                            break;
                        case 7 :
                            //小计
                            $cellvalue = $temp['goodsPaidPrice'];
                            break;
                    }
                } elseif ($type == 3) {
                    switch ($j) {
                        case 0 :
                            //供应商名称
                            $cellvalue = $temp['supplierName'];
                            break;
                        case 1 :
                            //商品名称
                            $cellvalue = $temp['goodsName'];
                            break;
                        case 2 :
                            //商品编码
                            $cellvalue = $temp['goodsSn'];
                            break;
                        case 3 :
                            //入库均价
                            $cellvalue = $temp['goodsCostPrice'];
                            break;
                        case 4 :
                            //入库数量
                            $cellvalue = $temp['goodsNums'];
                            break;
                        case 5 :
                            //一级分类
                            $cellvalue = $temp['goodsCatName1'];
                            break;
                        case 6 :
                            //二级分类
                            $cellvalue = $temp['goodsCatName2'];
                            break;
                        case 7 :
                            //三级分类
                            $cellvalue = $temp['goodsCatName3'];
                            break;
                        case 8 :
                            //小计
                            $cellvalue = $temp['goodsPaidPrice'];
                            break;
                    }
                }
                //赋值
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$j] . $row, $cellvalue);
                //设置字体大小
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(10);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return mixed
     * 损耗概况
     */
    public function storeLossSummary($startDate, $endDate, $shopId)
    {
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.shopId'] = $shopId;
        $fieldInfo = "sum(procureOrderNum) as procureOrderNum,sum(procureOrderMoney) as procureOrderMoney";
        $reportInfo = M('order_report sor')->where($where)->field($fieldInfo)->select();
        return $reportInfo;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 库房损耗
     */
    public function storeLoss($startDate, $endDate, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $endDate = date("Y-m-d", strtotime("$endDate +1 days")) . " 00:00:00";
        $endDate = strtotime($endDate);
        $startDate = $startDate . " 00:00:00";
        $startDate = strtotime($startDate);
        $where = [];
        $where['i.confirm_time'] = ['between', [$startDate, $endDate]];//确认时间
        $where['i.confirm_status'] = 1;//处理状态(0:未处理 1:已处理)
        $where['i.shop_id'] = $shopId;

        $reportInfo = M('inventory_bill i')
            ->join('left join wst_shops s on s.shopId = i.shop_id')
            ->where($where)
            ->field('i.*,s.shopName,s.shopSn')
            ->select();

        $billIds = array_get_column($reportInfo, 'bill_id');
        $goodsSkuId = [];

        $whereInfo = [];
        $whereInfo['ir.bill_id'] = ['IN', $billIds];
        if (!empty((int)I('goodsCatId1'))) {
            $whereInfo['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $whereInfo['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $whereInfo['wg.goodsCatId3'] = I('goodsCatId3');
        }

        $field = "ir.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,wg.goodsUnit,s.shopName";
        $res = M('inventory_bill_relation ir')
            ->join('left join wst_inventory_bill i on i.bill_id = ir.bill_id')
            ->join('left join wst_shops s on s.shopId = i.shop_id')
            ->join('left join wst_goods wg on wg.goodsId = ir.goods_id')
            ->join('left join wst_goods_cats wgc1 ON wgc1.catId = wg.goodsCatId1')
            ->join('left join wst_goods_cats wgc2 ON wgc2.catId = wg.goodsCatId2')
            ->join('left join wst_goods_cats wgc3 ON wgc3.catId = wg.goodsCatId3')
            ->where($whereInfo)
            ->field($field)
            ->group('ir.relation_id')
            ->select();

        foreach ($res as $val) {//按商品ID、skuId分组
            $goodsInfo = [];
            $goodsInfo['goods_id'] = $val['goods_id'];
            $goodsInfo['sku_id'] = $val['sku_id'];
            $goodsSkuId[] = $goodsInfo;
        }

        //去重
        $goodsSkuId = deWeight($goodsSkuId);

        $array = [];
        $numberSum = 0;//盘损总数
        $plateLossAmountSum = 0;//盘损总额
        foreach ($goodsSkuId as $k => $v) {
            $arr = [];
            $number = 0;//盘损数量
            $plateLossAmount = 0;//盘损金额
            $goodsPriceSum = 0;//商品库存进货总金额
            foreach ($res as $key => $val) {
                if ($val['goods_id'] == $v['goods_id'] && $val['sku_id'] == $v['sku_id']) {
                    if ($val['current_stock'] != $val['old_stock']) {
                        $number += $val['old_stock'] - $val['current_stock'];//盘点前库存 - 盘点后库存
                        $shopName = $val['shopName'];
                        $goodsName = $val['goodsName'];
                        $goodsSpec = $val['goodsSpec'];
                        $goodsCatName1 = $val['goodsCatName1'];
                        $goodsCatName2 = $val['goodsCatName2'];
                        $goodsCatName3 = $val['goodsCatName3'];
                        $goodsUnit = $val['goodsUnit'];//现在被用于进货价
                        $plateLossAmount += $goodsUnit * $number;//盘盈盘亏金额
                        $goodsPriceSum += $goodsUnit * $val['old_stock'];//盘点前库存
                        $goodsSn = $val['goodsSn'];//商品编码

                        $arr['shopName'] = $shopName;
                        $arr['goodsName'] = $goodsName;
                        $arr['goodsSn'] = $goodsSn;
                        $arr['goodsSpec'] = $goodsSpec;
                        $arr['goodsCatName1'] = $goodsCatName1;
                        $arr['goodsCatName2'] = $goodsCatName2;
                        $arr['goodsCatName3'] = $goodsCatName3;
                        $arr['goodsCatName3'] = $goodsCatName3;
                    }
                }
            }
            if (!empty($arr)) {
                $numberSum += $number;
                $plateLossAmountSum += $plateLossAmount;
                $arr['number'] = $number;
                $arr['plateLossAmount'] = formatAmountNum($plateLossAmount);
                $arr['percent'] = (bc_math($plateLossAmount, $goodsPriceSum, 'bcdiv', 4) * 100) . "%";//百分比--损耗金额占比
                $array[] = $arr;
            }
        }

        $arrayPage = arrayPage($array, $page, $pageSize);
        $list = [];
        $list['list'] = $arrayPage;
        $list['numberSum'] = $numberSum;
        $list['plateLossAmountSum'] = $plateLossAmountSum;
        if ($export == 1) {
            $this->exportStoreLoss($array, $list);
            exit();
        }
        return $list;
    }

    /**
     * @param $list
     * @param $reportDate
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出---库房损耗
     */
    public function exportStoreLoss($list, $reportDate)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = "库房盘点损耗";
        $excel_filename = '库房盘点损耗_' . date('Ymd_His');
        $sheet_title = array('商品名称', '商品编码', '描述', '一级分类', '二级分类', '三级分类', '盘损数量', '盘损金额', '损耗金额占比');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            if ($i == count($list)) {
                $temp = $reportDate;
                if (!empty($reportDate)) {
                    $temp['goodsName'] = "合计";
                    $temp['number'] = $reportDate['numberSum'];//盘损总数
                    $temp['plateLossAmount'] = $reportDate['plateLossAmountSum'];//盘损总额
                }
            }
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                switch ($j) {
                    case 0 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 1 :
                        //商品编码
                        $cellvalue = $temp['goodsSn'];
                        break;
                    case 2 :
                        //描述
                        $cellvalue = $temp['goodsSpec'];
                        break;
                    case 3 :
                        //一级分类
                        $cellvalue = $temp['goodsCatName1'];
                        break;
                    case 4 :
                        //二级分类
                        $cellvalue = $temp['goodsCatName2'];
                        break;
                    case 5 :
                        //三级分类
                        $cellvalue = $temp['goodsCatName3'];
                        break;
                    case 6 :
                        //盘损数量
                        $cellvalue = $temp['number'];
                        break;
                    case 7 :
                        //盘损金额
                        $cellvalue = $temp['plateLossAmount'];
                        break;
                    case 8 :
                        //损耗金额占比
                        $cellvalue = $temp['percent'];
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
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 退货报损
     */
    public function returnLoss($startDate, $endDate, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.is_return_goods'] = 1;//是否退货(-1:否|1:是)
        $where['sor.shopId'] = $shopId;
        if (!empty((int)I('goodsCatId1'))) {
            $where['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $where['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $where['wg.goodsCatId3'] = I('goodsCatId3');
        }

        $field = "wu.userName,sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,s.shopName";
        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);
        foreach ($data as $v) {
            $res[$v['goodsId']][] = $v;
        }
        $arr = [];
        $array = [];
        $goodsNumSum = 0;//销售数量总数
        $goodsCostPriceSum = 0;//销售成本价总价
        $goodsPaidPriceSum = 0;//销售实付金额总价
        $refundGoodsCountSum = 0;//退货商品数量总数
        $refundCostPriceSum = 0;//退货成本价总价
        $refundFeeSum = 0;//退货金额总价【用户退货】
        $goodsCostPricesSum = 0;//商品成本价总
        foreach ($res as $v) {
            $goodsNums = 0;//销售数量
            $goodsPaidPrice = 0;//商品实付金额
            $refundOrderCount = 0;//退货订单数量
            $refundGoodsCount = 0;//退货商品数量
            $refundFee = 0;//退货金额
            $refundCostPriceNum = 0;//退货成本价
            $refundFeeTotal = 0;//退货金额
            $goodsPriceNum = 0;//商品实际金额
            $goodsCostPriceNum = 0;//销售成本价
            $goodsCostPrice = 0;//进货价--成本价
            $goodsCostPrices = 0;//当前商品成本价
            foreach ($v as $val) {
                $refundFeeTotal += $val['refundFee'];
                $refundFee += $val['refundFee'];//退货金额
                $goodsPriceNum += $val['goodsNums'] * $val['goodsPrice'];//商品实际金额
                $refundOrderCount += 1;
                $refundGoodsCount += $val['goodsNums'];
                $goodsNums += $val['goodsNums'];
                $goodsPaidPrice += $val['goodsPaidPrice'];//商品实付金额
                //商品基本信息
                $shopName = $val['shopName'];
                $goodsName = $val['goodsName'];
                $goodsSn = $val['goodsSn'];
                $goodsDesc = $val['goodsSpec'];
                $goodsCatName1 = $val['goodsCatName1'];
                $goodsCatName2 = $val['goodsCatName2'];
                $goodsCatName3 = $val['goodsCatName3'];

                $refundCostPriceNum += $goodsCostPrice * $refundGoodsCount;//退货成本价
                $goodsCostPrices += $val['goodsCostPrice'] * $val['goodsNums'];//当前商品成本价
            }
            $goodsPaidPriceSum += formatAmountNum($goodsPaidPrice);//销售实付金额总价
            $goodsNumSum += $goodsNums;//销售数量总数
            $goodsCostPriceSum += $goodsCostPriceNum;//销售成本价总价
            $refundCostPriceSum += $refundCostPriceNum;//退货成本价总价
            $refundGoodsCountSum += $refundGoodsCount;//退货商品数量总数
            $refundFeeSum += $refundFee;//退货商品金额总价
            $goodsCostPricesSum += $goodsCostPrices;//商品成本价总
            //商品信息
            $arr['shopName'] = $shopName;
            $arr['goodsName'] = $goodsName;
            $arr['goodsSn'] = $goodsSn;
            $arr['goodsSpec'] = $goodsDesc;
            $arr['goodsCatName1'] = $goodsCatName1;
            $arr['goodsCatName2'] = $goodsCatName2;
            $arr['goodsCatName3'] = $goodsCatName3;
            $arr['goodsNums'] = $goodsNums;//发货数量
            $arr['refundGoodsCount'] = $refundGoodsCount;//报损数量
            $arr['refundFee'] = formatAmountNum($refundFee);//报损金额
            $arr['refundPercent'] = (bc_math($refundGoodsCount, $goodsNums, 'bcdiv', 4) * 100) . '%';//报损率
            $arr['refundPricePercent'] = (bc_math($refundFee, $refundFeeTotal, 'bcdiv', '4') * 100) . '%';//报损金额占比
            $array[] = $arr;
        }
        $arrayPage = arrayPage($array, $page, $pageSize);
        $list = [];
        $list['list'] = $arrayPage;
        $reportDate = [];
        $reportDate['goodsNumSum'] = $goodsNumSum;//销售数量总数----发货数量总数
        $reportDate['refundGoodsCountSum'] = $refundGoodsCountSum;//退货数量----报损数量
        $reportDate['refundPercentSum'] = (bc_math($refundGoodsCountSum, $goodsNumSum, 'bcdiv', 4) * 100) . '%';//报损率
        $reportDate['refundFeeSum'] = formatAmountNum($refundFeeSum);//损耗总金额
        $list['commodity'] = $reportDate;
        if ($export == 1) {
            $this->exportReturnLoss($array);
            exit();
        }
        return $list;
    }

    /**
     * @param $list
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出---退货报损
     */
    public function exportReturnLoss($list)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = "退货报损";
        $excel_filename = '退货报损_' . date('Ymd_His');
        $sheet_title = array('商品名称', '商品编码', '描述', '一级分类', '二级分类', '三级分类', '发货数量', '报损数量', '报损金额', '报损率', '报损金额占比');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K');
        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                switch ($j) {
                    case 0 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 1 :
                        //商品编码
                        $cellvalue = $temp['goodsSn'];
                        break;
                    case 2 :
                        //描述
                        $cellvalue = $temp['goodsSpec'];
                        break;
                    case 3 :
                        //一级分类
                        $cellvalue = $temp['goodsCatName1'];
                        break;
                    case 4 :
                        //二级分类
                        $cellvalue = $temp['goodsCatName2'];
                        break;
                    case 5 :
                        //三级分类
                        $cellvalue = $temp['goodsCatName3'];
                        break;
                    case 6 :
                        //发货数量
                        $cellvalue = $temp['goodsNums'];
                        break;
                    case 7 :
                        //报损数量
                        $cellvalue = $temp['refundGoodsCount'];
                        break;
                    case 8 :
                        //报损金额
                        $cellvalue = $temp['refundFee'];
                        break;
                    case 9 :
                        //报损率
                        $cellvalue = $temp['refundPercent'];
                        break;
                    case 10 :
                        //报损金额占比
                        $cellvalue = $temp['refundPricePercent'];
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
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @param $shopId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 报损损耗
     */
    public function breakageLoss($startDate, $endDate, $shopId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $endDate = date("Y-m-d", strtotime("$endDate +1 days")) . " 00:00:00";
        $endDate = strtotime($endDate);
        $startDate = $startDate . " 00:00:00";
        $startDate = strtotime($startDate);
        $where = [];
        $where['il.confirm_time'] = ['between', [$startDate, $endDate]];//确认时间
        $where['il.confirm_status'] = 1;//确认状态(0:未确认 1:已确认)
        $where['il.is_delete'] = 0;//删除状态(0:未删除 1:已删除)
        $where['il.shop_id'] = $shopId;//店铺id
        if (!empty((int)I('goodsCatId1'))) {
            $where['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $where['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $where['wg.goodsCatId3'] = I('goodsCatId3');
        }

        $field = "il.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,wg.goodsUnit,wg.goodsStock,s.shopName";
        $reportModule = new ReportModule();
        $data = $reportModule->getInventoryLossList($where, $field);

        $goodsSkuId = [];
        foreach ($data as $val) {//按商品ID、skuId分组
            $goodsInfo = [];
            $goodsInfo['goods_id'] = $val['goods_id'];
            $goodsInfo['sku_id'] = $val['sku_id'];
            $goodsSkuId[] = $goodsInfo;
        }

        //去重
        $goodsSkuId = deWeight($goodsSkuId);

        $array = [];
        $numberSum = 0;//报损总数
        $plateLossAmountSum = 0;//报损总额
        foreach ($goodsSkuId as $k => $v) {
            $plateLossAmount = 0;//报损金额
            $lossNum = 0;//报损数量
            $goodsPriceAmount = 0;//现有库存金额 todo：这个可能有问题
            $arr = [];
            foreach ($data as $key => $val) {
                if ($v['goods_id'] == $val['goods_id'] && $v['sku_id'] == $val['sku_id']) {
                    $lossNum += $val['loss_num'];//报损数量
                    $shopName = $val['shopName'];
                    $goodsName = $val['goodsName'];
                    $goodsSpec = $val['goodsSpec'];
                    $goodsCatName1 = $val['goodsCatName1'];
                    $goodsCatName2 = $val['goodsCatName2'];
                    $goodsCatName3 = $val['goodsCatName3'];
                    $goodsUnit = $val['goodsUnit'];//现在被用于进货价
                    $plateLossAmount += $goodsUnit * $val['loss_num'];//报损金额
                    $goodsPriceAmount += $goodsUnit * $val['goodsStock'];//现有库存金额 todo：这个可能有问题
                    $goodsSn = $val['goodsSn'];//商品编码

                    $arr['shopName'] = $shopName;
                    $arr['goodsName'] = $goodsName;
                    $arr['goodsSn'] = $goodsSn;
                    $arr['goodsSpec'] = $goodsSpec;
                    $arr['goodsCatName1'] = $goodsCatName1;
                    $arr['goodsCatName2'] = $goodsCatName2;
                    $arr['goodsCatName3'] = $goodsCatName3;
                    $arr['goodsCatName3'] = $goodsCatName3;
                }
            }
            $numberSum += $lossNum;//报损数量总和
            $plateLossAmountSum += $plateLossAmount;
            $arr['number'] = $lossNum;//报损数量
            $arr['breakageLossAmount'] = formatAmountNum($plateLossAmount);
            $arr['percent'] = (bc_math($plateLossAmount, $goodsPriceAmount, 'bcdiv', 4) * 100) . "%";//百分比--损耗金额占比
            $array[] = $arr;
        }
        $arrayPage = arrayPage($array, $page, $pageSize);
        $list = [];
        $list['list'] = $arrayPage;
        $list['numberSum'] = $numberSum;
        $list['breakageLossAmountSum'] = $plateLossAmountSum;
        if ($export == 1) {
            $this->exportBreakageLoss($array, $list);
            exit();
        }
        return $list;
    }

    /**
     * @param $list
     * @param $reportDate
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 导出报损损耗
     */
    public function exportBreakageLoss($list, $reportDate)
    {
        Vendor("PHPExcel.PHPExcel");
        Vendor("PHPExcel.PHPExcel.IOFactory");
        $objPHPExcel = new \PHPExcel();
        $title = "报损损耗";
        $excel_filename = '报损损耗_' . date('Ymd_His');
        $sheet_title = array('商品名称', '商品编码', '描述', '一级分类', '二级分类', '三级分类', '报损数量', '报损金额', '报损损耗金额占比');
        $letter = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I');
        $letterCount = '';
        for ($i = 0; $i < count($list) + 1; $i++) {
            //先确定行
            $row = $i + 3;//再确定列，最顶部占一行，表头占用一行，所以加3
            $temp = $list[$i];
            if ($i == count($list)) {
                $temp = $reportDate;
                if (!empty($reportDate)) {
                    $temp['goodsName'] = "合计";
                    $temp['number'] = $reportDate['numberSum'];//报损总数
                    $temp['breakageLossAmount'] = $reportDate['breakageLossAmountSum'];//报损总额
                }
            }
            $letterCount = count($letter);
            for ($j = 0; $j < $letterCount; $j++) {
                switch ($j) {
                    case 0 :
                        //商品名称
                        $cellvalue = $temp['goodsName'];
                        break;
                    case 1 :
                        //商品编码
                        $cellvalue = $temp['goodsSn'];
                        break;
                    case 2 :
                        //描述
                        $cellvalue = $temp['goodsSpec'];
                        break;
                    case 3 :
                        //一级分类
                        $cellvalue = $temp['goodsCatName1'];
                        break;
                    case 4 :
                        //二级分类
                        $cellvalue = $temp['goodsCatName2'];
                        break;
                    case 5 :
                        //三级分类
                        $cellvalue = $temp['goodsCatName3'];
                        break;
                    case 6 :
                        //报损数量
                        $cellvalue = $temp['number'];
                        break;
                    case 7 :
                        //报损金额
                        $cellvalue = $temp['breakageLossAmount'];
                        break;
                    case 8 :
                        //报损损耗金额占比
                        $cellvalue = $temp['percent'];
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
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(50);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }
}