<?php

namespace Adminapi\Model;

use App\Models\OrderComplainsrecordModel;
use App\Modules\Orders\OrdersModule;
use App\Modules\Orders\OrdersServiceModule;
use App\Modules\Report\ReportModule;
use App\Modules\Users\UsersModule;

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
     * @param $params
     * @return array
     * 获取营业数据列表---报表统计总表
     */
    public function businessList($params)
    {
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];

        $limitPate = ($page - 1) * $pageSize;
        $where = " wor.`reportDate` BETWEEN '{$startDate}' AND '{$endDate}'";
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }

        $fieldInfo = "wor.*,s.shopName,s.shopSn";
        $sql = " select {$fieldInfo} from `wst_order_report` wor left join  wst_shops s on s.shopId = wor.shopId where " . $where;
        $data = $this->pageQuery($sql, $page, $pageSize);
        if (!empty($data['root'])) {
            foreach ($data['root'] as &$item) {
                $item['practicalPrice'] = bc_math($item['salesOrderMoney'], $item['salesRefundOrderMoney'], 'bcsub', 2);
            }
            unset($item);
        }
        $field = "sum(a.salesRefundOrderNum) as salesRefundOrderNumCount,sum(a.salesRefundOrderMoney) as salesRefundOrderMoneyCount,sum(a.salesOrderNum) as salesOrderNumCount,sum(a.salesOrderMoney) as salesOrderMoneyCount,sum(a.procureOrderNum) as procureOrderNumCount,sum(a.procureOrderMoney) as procureOrderMoneyCount";
        $sqlSum = " select {$field} from ({$sql} limit $limitPate,$pageSize) as a";
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
     * @param $params
     * @return array
     * 获取营业数据列表---报表统计总表
     */
    public function businessListTwo($params)
    {
        //复制上面的代码,主逻辑不变,只是数据源临时获取,上面的方式得出的数据结果无法满足客户需求
        //这里只统计订单相关!产品设计之初就未考虑到其他的购买金额统计,目前只有订单统计中统计了其他购买金额
        $startDate = $params['startDate'];
        $endDate = $params['endDate'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];

//        $limitPate = ($page - 1) * $pageSize;
        $where = " wor.`reportDate` BETWEEN '{$startDate}' AND '{$endDate}'";
        //店铺名称|编号
        if (!empty($params['shopWords'])) {
            $where .= " and (s.shopName like '%{$params['shopWords']}%' or s.shopSn like '%{$params['shopWords']}%') ";
        }

        $fieldInfo = "wor.*,s.shopName,s.shopSn";
        $sql = " select {$fieldInfo} from `wst_order_report` wor left join  wst_shops s on s.shopId = wor.shopId where " . $where;
        $data = $this->pageQuery($sql, $page, $pageSize);
        $list = $data['root'];
        $pay_start_time = $startDate . ' 00:00:00';
        $pay_end_time = $endDate . ' 23:59:59';
        $orders_tab = M('orders');
        $order_list = $orders_tab->where(array(
            'pay_time' => array('between', array($pay_start_time, $pay_end_time)),
            'isPay' => 1
        ))->select();
        $time_order_list_map = [];
        $salesOrderMoneyCount = 0; //订单销售金额
        $salesRefundOrderNumCount = 0;//订单退款数量
        $salesRefundOrderMoneyCount = 0;//订单退款金额
        foreach ($order_list as $order_list_row) {
            $order_pay_time = date('Y-m-d', strtotime($order_list_row['pay_time']));
            $order_list_row['pay_time'] = $order_pay_time;
            $time_order_list_map[$order_list_row['shopId'] . '_' . $order_list_row['pay_time']][] = $order_list_row;
            $salesOrderMoneyCount = bc_math($salesOrderMoneyCount, $order_list_row['realTotalMoney'], 'bcadd', 2);
        }
        foreach ($list as $key => $list_row) {
            $salesRefundOrderNumCount = bc_math($salesRefundOrderNumCount, $list_row['salesRefundOrderNum'], 'bcadd', 0); //这个和订单统计模块保持一致
            $salesRefundOrderMoneyCount = bc_math($salesRefundOrderMoneyCount, $list_row['salesRefundOrderMoney'], 'bcadd', 2); //这个和订单统计模块保持一致
            $curr_time_orders = $time_order_list_map[$list_row['shopId'] . "_" . $list_row['reportDate']];
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
     * @return mixed
     * 获取营业数据详情----按分类查看
     */
    public function businessDetail($reportId)
    {
        $field = "sor.*,wgc3.catName";
        $where = [];
        $where['sor.reportId'] = $reportId;
        $where['sor.otpId'] = ['elt', 0];

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
            // $goodsCostPrice = 0;//进货价
            // $goodsPaidPrice = 0;//商品实付金额
            // $refundFee = 0;//退款金额
            // foreach ($v as $val) {
            //     $goodsCostPrice += bc_math($val['goodsCostPrice'], $val['goodsNums'], 'bcmul', 2);
            //     $goodsPaidPrice += $val['goodsPaidPrice'];
            //     $total_price += bc_math($val['goodsCostPrice'], $val['goodsNums'], 'bcmul', 2);
            //     $refundFee += $val['refundFee'];
            //     $catName = $val['catName'];
            // }
            // $array['catName'] = $catName;
            // $array['goodsCostPriceCount'] = formatAmount($goodsCostPrice);
            // $array['goodsPaidPriceCount'] = formatAmount($goodsPaidPrice);
            // $array['refundFeeCount'] = formatAmount($refundFee);
            // $array['percent'] = (bc_math($goodsCostPrice, $goodsPaidPrice, 'bcdiv', 4) * 100) . '%';
            // $arr[] = $array;
        }
        $list = [];
        $list['list'] = $arr;
        $list['total_price'] = formatAmount($total_price);
        return $list;
    }

    /**
     * @param $reportId 报表id
     * @return mixed
     * 获取营业数据详情----按分类查看
     */
    public function businessDetailTwo($reportId)
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
        ];
        $report_row = $report_tab->where($report_where)->find();
        $shopId = $report_row['shopId'];
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
     * @param $type 1:按商品统计|2：按分类统计
     * @param $profitType
     * @return array
     * @throws \PHPExcel_Exception
     * 获取商品销售列表
     * $profitType 销售毛利状态
     */
    public function commoditySaleList($startDate, $endDate, $type, $profitType = 0)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//1:导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
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
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        $field = "sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,s.shopName,s.shopSn";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);


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
                $shopsName = $val['shopName'];
                $shopsSn = $val['shopSn'];
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
                    $shopsName = $val['shopName'];
                    $shopsSn = $val['shopSn'];
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
            $salesOrderMoney += $goodsPaidPrice;//销售订单总金额
            $salesRefundOrderMoney += $refundFee;//退货金额
            $refundGoodsCostPriceSum += $refundGoodsCostPrice;//退货总成本
            $profitSum += $profit;//所有毛利
            $goodsCostPriceSum += $goodsCostPrice;//所有商品成本价
            $practicalSum += $goodsPriceNum;//所有商品的实际价格
            $arr['goodsId'] = $goodsId;
            $arr['goodsCatId3'] = $goodsCatId3;
            $arr['goodsCatName3'] = $goodsCatName3;
            $arr['shopName'] = $shopsName;//商品名称
            $arr['shopSn'] = $shopsSn;
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
                $arr['goodsName'] = $goodsName;
                $arr['goodsSn'] = $goodsSn;
                $arr['shopName'] = $shopsName;
                $arr['shopSn'] = $shopsSn;
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
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
        }
        exportGoods($title, $excel_filename, $sheet_title, $letter, $objPHPExcel, $letterCount);
    }

    /**
     * @param $goodsId
     * @param $goodsCatId3
     * @param int $profitType
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 商品销售----获取用户商品销量明细---根据商品ID获取
     * $profitType 销售毛利状态
     */
    public function commoditySalesUserDetail($goodsId, $goodsCatId3, $profitType = 0)
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
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        $field = "wu.userName,sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,s.shopName,s.shopSn";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

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
                if ($val['isRefund'] == 1) {//退款
                    $refundOrderCount += 1;
                    $refundGoodsCount += $val['goodsNums'];
                } else {
                    $goodsNum = $val['goodsNums'];
                    $goodsNums += $goodsNum;
                    $goodsPaidPrice += $val['goodsPaidPrice'];//商品实付金额
                    $goodsPrice = $val['goodsNums'] * $val['goodsPrice'];//商品实际金额
                }
                $goodsPriceNum += $goodsPrice;
                if ($type == 1) {
                    //商品基本信息
                    $goodsName = $val['goodsName'];
                    $goodsSn = $val['goodsSn'];
                    $goodsDesc = $val['goodsSpec'];
                    $goodsCatName1 = $val['goodsCatName1'];
                    $goodsCatName2 = $val['goodsCatName2'];
                }
                $shopsName = $val['shopName'];
                $shopsSn = $val['shopSn'];
                $goodsCatName3 = $val['goodsCatName3'];
                //客户	退货数量	退货金额	退货成本	实际金额	实际成本	毛利	毛利率
                if ($profitType == 1) {
                    $goodsCostPrice = $val['goodsCostPrice'];//成本单价
                    $goodsCost = $goodsCostPrice * $goodsNum;//销售成本价
                    $goodsCostPriceNum += $goodsCost;
                    $refundCostPriceNum += $goodsCostPrice * $refundGoodsCount;//退款成本价
                    $goodsCostPrices += $val['goodsCostPrice'] * $val['goodsNums'];//当前商品成本价
                    $profitNum = $goodsPrice - $goodsCost;//毛利
                    $profit += $profitNum;
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
                $arr['profitPercent'] = $profitPercent;//毛利率
                $arr['profit'] = formatAmountNum($profit);//毛利
                $arr['reportDate'] = $reportDate;//时间
                $arr['shopName'] = $shopsName;
                $arr['shopSn'] = $shopsSn;
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
            $goodsData['shopName'] = $shopsName;
            $goodsData['shopSn'] = $shopsSn;
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
     * @param $profitType
     * @return array
     * 商品销售---图表模式
     * $profitType 销售毛利状态
     */
    public function CommoditySaleData($startDate, $endDate, $profitType = 0)
    {
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        $field = "sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,s.shopName,s.shopSn";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);

        foreach ($data as $v) {
            $res[$v['goodsId']][] = $v;
        }
        $arr = [];
        $array = [];
        $return = [];
        $salesRefundOrderNum = 0;//订单退款数量
        $salesRefundOrderMoney = 0;//订单退款金额
        $salesOrderNum = 0;//销售订单数量
        $salesOrderMoney = 0;//销售订单总金额
        $goodsCostPriceSum = 0;//成本价总金额
        $profitSum = 0;//毛利总金额
        $goodsNums = 0;//销售商品数量
        $refundFeeGoodsPriceSum = 0;//退款商品成本总价格
        foreach ($res as $v) {
            $goodsPaidPrice = 0;//商品实付金额
            $refundFee = 0;//退款金额
            $goodsCostPrice = 0;//成本价
            $goodsPriceNum = 0;//商品实际金额
            $profit = 0;//毛利
            $goodsPrice = 0;//销售商品价格
            foreach ($v as $val) {
                $reportInfo = M('order_report sor')->where(['reportId' => $val['reportId']])->find();
                if ($val['isRefund'] == 1) {//退款
                    $refundFee += $val['refundFee'];
                    $refundFeeGoodsPrice = $val['goodsNums'] * $val['goodsCostPrice'];//退款商品成本价格
                    $refundFeeGoodsPriceSum += $refundFeeGoodsPrice;
                } else {
                    $goodsPaidPrice += $val['goodsPaidPrice'];
                    $goodsNum = $val['goodsNums'];
                    $goodsNums += $goodsNum;
                    $goodsPrice = $val['goodsNums'] * $val['goodsPrice'];//销售商品价格
                }
                $goodsName = $val['goodsName'];
                $goodsPriceNum += $goodsPrice;
                if ($profitType == 1) {
                    $goodsCost = $val['goodsCostPrice'] * $goodsNum;//销售商品成本价
                    $goodsCostPrice += $goodsCost;
                    $profitNum = $goodsPrice - $goodsCost;//毛利
                    $profit += $profitNum;
                }
            }
            $salesRefundOrderNum += $reportInfo['salesRefundOrderNum'];//订单退款数量
            $salesRefundOrderMoney += $reportInfo['salesRefundOrderMoney'];//订单退款金额
            $salesOrderNum += $reportInfo['salesOrderNum'];//销售订单数量
            $salesOrderMoney += $goodsPriceNum;//销售订单总金额
            $arr['goodsName'] = $goodsName;
            $arr['goodsPaidPrice'] = formatAmountNum($goodsPaidPrice);//商品实付金额
            $arr['percent'] = round($goodsPaidPrice / $reportInfo['salesOrderMoney'] * 100, 1) . "％";
            $returnList['goodsName'] = $goodsName;
            $returnList['refundFee'] = formatAmountNum($refundFee);//退款金额
            $returnList['percent'] = round($refundFee / $reportInfo['salesRefundOrderMoney'] * 100, 1) . "％";
            $array[] = $arr;
            $return[] = $returnList;
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
        $list['saleList'] = $arrayCount;
        $list['returnList'] = array_slice($return, 0, 10);
        if ($profitType == 1) {
            $list = [];
            $list['salesOrderMoney'] = formatAmountNum($salesOrderMoney);//实付金额
            $list['goodsCostPriceSum'] = formatAmountNum($goodsCostPriceSum);//实际成本
            $list['profitSum'] = formatAmountNum($profitSum);//毛利总金额
            $list['profitPercent'] = round($profitSum / $salesOrderMoney * 100, 2) . "％";//毛利率
        }
        return $list;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 获取订单统计列表
     */
    public function statOrder($startDate, $endDate)
    {
        //复制上面注释的方法,改为临时统计,业务稳定后,在做数据表统计
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $where = array();
        $where['wor.reportDate'] = ['between', [$startDate, $endDate]];
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }
        $orderReportField = 'wor.*,s.shopName,s.shopSn';
        $reportModule = new ReportModule();
        $reportData = $reportModule->getOrderReportList($where, $orderReportField);

        //参考wst_order_report表
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
        $alipay_money_out_total = 0;//支付宝退款金额【包含补差价、售后】A
        $cash_money_out_total = 0;//余额退款金额【包含补差价、售后】A

        $wx_cash_recharge_total = 0;//微信余额充值总金额A
        $wx_set_meal_total = 0;//微信开通会员总金额A
        $wx_coupon_total = 0;//微信购买优惠券总金额A

        $alipay_cash_recharge_total = 0;//支付宝余额充值总金额A
        $alipay_set_meal_total = 0;//支付宝开通会员总金额A
        $alipay_coupon_total = 0;//支付宝购买优惠券总金额A

        $orders_tab = M('orders');
        $mod_order_complainsrecord = M('order_complainsrecord');//售后退款记录表
        $mod_goods_pricediffe = M('goods_pricediffe');//商品差价补款表

        $pay_start_time = $startDate . ' 00:00:00';
        $pay_end_time = $endDate . ' 23:59:59';
        $whereInfo = [];
        $whereInfo['pay_time'] = ['between', [$pay_start_time, $pay_end_time]];
        $whereInfo['isPay'] = 1;
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $whereInfo['_complex'] = $maps;
        }
        $order_list = $orders_tab->alias('o')
            ->join('left join wst_shops s on s.shopId = o.shopId')
            ->where($whereInfo)
            ->field('o.*')
            ->select();
        foreach ($order_list as &$item) {
            $item['pay_time'] = date('Y-m-d', strtotime($item['pay_time']));
        }
        unset($item);
        $order_list_map = [];
        foreach ($order_list as $order_list_row) {
            $order_list_map[$order_list_row['orderId']] = $order_list_row;
        }
//        $coupon_tab = M('coupons');
        $orders_module = new OrdersModule();
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
        $whereInfo = [];
        $whereInfo['oc.addTime'] = ['between', [$pay_start_time, $pay_end_time]];
        $whereInfo['oc.complainId'] = ['GT', 0];
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $whereInfo['_complex'] = $maps;
        }
        $order_complainsrecord_list = $mod_order_complainsrecord->alias('oc')
            ->join("left join wst_orders wo on wo.orderId = oc.orderId")
            ->join('left join wst_shops s on s.shopId = wo.shopId')
            ->where($whereInfo)
            ->field('oc.*')
            ->select();//售后退款记录money
        $complainsrecord_order_id_arr = array_column($order_complainsrecord_list, 'orderId');
        $complainsrecord_order_list_map = [];
        if (count($complainsrecord_order_id_arr) > 0) {
            $complainsrecord_order_id_arr = array_unique($complainsrecord_order_id_arr);
            $complainsrecord_order_list = $orders_module->getOrderListById($complainsrecord_order_id_arr);
            foreach ($complainsrecord_order_list as $complainsrecord_order_list_row) {
                $complainsrecord_order_list_map[$complainsrecord_order_list_row['orderId']] = $complainsrecord_order_list_row;
            }
        }
        $order_complainsrecord_money_wx = 0;//微信 售后
        $order_complainsrecord_money_ye = 0;//余额 售后
        $order_complainsrecord_money_alipay = 0;//支付宝 售后
        foreach ($order_complainsrecord_list as $v) {
//            $orderInfo = $orders_tab->where(['orderId' => $v['orderId']])->find();
            $orderInfo = $complainsrecord_order_list_map[$v['orderId']];

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
        $whereInfo = [];
        $whereInfo['gp.payTime'] = ['between', [$pay_start_time, $pay_end_time]];
        $whereInfo['gp.isPay'] = 1;
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $whereInfo['_complex'] = $maps;
        }
        $goods_pricediffe_list = $mod_goods_pricediffe->alias('gp')
            ->join("left join wst_orders wo on wo.orderId = gp.orderId")
            ->join('left join wst_shops s on s.shopId = wo.shopId')
            ->where($whereInfo)
            ->field('gp.*')
            ->select();//商品差价补款
        $pricediffe_order_id_arr = array_column($goods_pricediffe_list, 'orderId');
        $pricediffe_order_lsit_map = [];
        if (count($pricediffe_order_id_arr) > 0) {
            $pricediffe_order_id_arr = array_unique($pricediffe_order_id_arr);
            $pricediffe_order_lsit = $orders_module->getOrderListById($pricediffe_order_id_arr);
            foreach ($pricediffe_order_lsit as $pricediffe_order_lsit_row) {
                $pricediffe_order_lsit_map[$pricediffe_order_lsit_row['orderId']] = $pricediffe_order_lsit_row;
            }
        }

        $goods_pricediffe_money_wx = 0;//微信 商品差价补款
        $goods_pricediffe_money_ye = 0;//余额 商品差价补款
        $goods_pricediffe_money_alipay = 0;//支付宝 商品差价补款
        foreach ($goods_pricediffe_list as $v) {
//            $orderInfo = $orders_tab->where(['orderId' => $v['orderId']])->find();
            $orderInfo = $pricediffe_order_lsit_map[$v['orderId']];
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
        $alipay_money_out_total += bc_math($order_complainsrecord_money_alipay, $goods_pricediffe_money_alipay, 'bcadd', 2);//支付宝退款金额【包含补差价、售后】
        $cash_money_out_total += bc_math($order_complainsrecord_money_ye, $goods_pricediffe_money_ye, 'bcadd', 2);//余额退款金额【包含补差价、售后】

        //会员充值金额、套餐开通金额、优惠券购买金额 暂时合计到微信总进账里
        $whereInfo = [];
        $whereInfo['nlt.requestTime'] = ['between', [$pay_start_time, $pay_end_time]];
        $whereInfo['nlt.notifyStatus'] = 1;
        $whereInfo['nlt.type'] = ['IN', [3, 4, 5]];
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $whereInfo['_complex'] = $maps;
        }
        $notify_log_tab = M('notify_log');
        $smId_arr = [];
        $set_meal_list_map = [];
        $csId_arr = [];
        $coupon_set_list_map = [];
        $notify_log_list = $notify_log_tab->alias('nlt')
            ->join("left join wst_orders wo on wo.orderToken = nlt.orderToken")
            ->join('left join wst_shops s on s.shopId = wo.shopId')
            ->where($whereInfo)
            ->field('nlt.id,nlt.requestJson,nlt.type,nlt.notifyStatus,nlt.requestTime')
            ->select();
        foreach ($notify_log_list as &$item) {
            $item['requestTime'] = date('Y-m-d', strtotime($item['requestTime']));
            $type = $item['type'];
            $request_arr = json_decode($item['requestJson'], true);
            if ($type == 4) {
                $smId_arr[] = (int)$request_arr['smId'];
            } elseif ($type == 5) {
                $csId_arr[] = (int)$request_arr['csId'];
            }
        }
        unset($item);
        $set_meal_tab = M('set_meal');//套餐表
        $coupon_set_tab = M('coupon_set');

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
        //弃用统计表,直接临时计算
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
            $order_complainsrecord_order_id_arr = array_column($orderComplainsrecordList, 'orderId');
            $order_complainsrecord_order_list_map = [];
            if (count($order_complainsrecord_order_id_arr) > 0) {
                $order_complainsrecord_order_id_arr = array_unique($order_complainsrecord_order_id_arr);
                $order_complainsrecord_order_list = $orders_module->getOrderListById($order_complainsrecord_order_id_arr);
                foreach ($order_complainsrecord_order_list as $order_complainsrecord_order_list_row) {
                    $order_complainsrecord_order_list_map[$order_complainsrecord_order_list_row['orderId']] = $order_complainsrecord_order_list_row;
                }

            }

            $orderComplainsrecordMoneyWx = 0;//微信 售后
            $orderComplainsrecordMoneyAlipay = 0;//支付宝 售后
            $orderComplainsrecordMoneyYe = 0;//余额 售后
            foreach ($orderComplainsrecordList as $value) {
//                $orderInfo = $orders_tab->where(['orderId' => $value['orderId']])->find();
                $orderInfo = $order_complainsrecord_order_list_map[$value['orderId']];
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
                $goods_pricediffe_orders_list = $orders_module->getOrderListById($goods_pricediffe_orders_id_arr);
                foreach ($goods_pricediffe_orders_list as $goods_pricediffe_orders_list_row) {
                    $goods_pricediffe_orders_list_map[$goods_pricediffe_orders_list_row['orderId']] = $goods_pricediffe_orders_list_row;
                }
            }

            $goodsPricediffeMoneyWx = 0;//微信 商品差价补款
            $goodsPricediffeMoneyAlipay = 0;//支付宝 商品差价补款
            $goodsPricediffeMoneyYe = 0;//余额 商品差价补款
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
            $alipay_money_out_signle += bc_math($orderComplainsrecordMoneyAlipay, $goodsPricediffeMoneyAlipay, 'bcadd', 2);//支付宝退款金额【包含补差价、售后】
            $cash_money_out_signle += bc_math($orderComplainsrecordMoneyYe, $goodsPricediffeMoneyYe, 'bcadd', 2);//余额退款金额【包含补差价、售后】

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
//                        $smId = (int)$request_arr['smId'];
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
                    $temp['cash_money_signle'] = $reportDate['cash_money_total'];
                    $temp['wxpay_money_signle'] = $reportDate['wxpay_money_total'];
                    $temp['cash_money_out_signle'] = $reportDate['cash_money_out_total'];
                    $temp['wxpay_money_out_signle'] = $reportDate['wxpay_money_out_total'];
                    $temp['wx_cash_recharge_signle'] = $reportDate['wx_cash_recharge_total'];
                    $temp['wx_set_meal_signle'] = $reportDate['wx_set_meal_total'];
                    $temp['wx_coupon_signle'] = $reportDate['wx_coupon_total'];
                    $temp['cash_money_real_signle'] = $reportDate['cash_money_real_total'];
                    $temp['wxpay_money_real_signle'] = $reportDate['wxpay_money_real_total'];
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
//                        $cellvalue = $temp['cash_money_signle'];
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
     * @param int $profitType
     * @param int $userType
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 获取客户统计列表
     * $profitType 销售毛利状态
     */
    public function getUsersList($startDate, $endDate, $profitType = 0, $userType = 0)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }
        if (!empty(I('goodsSn'))) {//商品编码
            $where['wg.goodsSn'] = I('goodsSn');
        }
        $field = "wu.userId,wu.userName,sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,s.shopName,s.shopSn";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);


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
            $orderNum = count($users_order_id_arr);
            $saleCount = 0;//销售笔数
            $salePriceCount = 0;//销售金额
//            $users_order_list = $orders_tab->where(array('orderId' => array('IN', $users_order_id_arr)))->select();
            $users_order_list = [];
            foreach ($users_order_id_arr as $users_order_id) {
                $order_list_map_row = $order_list_map[$users_order_id];
                if (empty($order_list_map_row)) {
                    continue;
                }
                $users_order_list[] = $order_list_map_row;
            }
            //新加字段
            $coupon_money_signle = 0;//优惠券金额
            $goods_money_signle = 0;//商品金额
            $need_money_signle = 0;//订单应收金额
            $score_money_signle = 0;//积分抵扣金额
            $delivery_money_signle = 0;//配送费金额
            $cash_money_signle = 0;//余额支付
            $wxpay_money_signle = 0;//微信支付金额
            $saleCountArr = array();//用于bug修复
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
                $coupon_money_total += (float)$item['coupon_use_money'];
                $coupon_money_total += (float)$item['delivery_coupon_use_money'];

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
            $goodsNums = 0;//商品数量
            $saleGoodsCountNum = 0;//销售商品数量
            $returnCostPriceNum = 0;//退款成本价
            foreach ($v as $val) {
                $saleCountArr[$val['orderId']] = $val['orderId'];
                $userName = $val['userName'];//客户名称
                $userId = $val['userId'];//客户Id
                $shopName = $val['shopName'];//店铺名称
                if ($val['is_return_goods'] == 1) {
                    $returnCount += 1;
                    $returnPriceCount += $val['refundFee'];
                    if ($val['is_cancel_order'] == 1) {
//                        $cancel_info = $orders_tab->where(array('orderId' => $val['orderId']))->find();
                        $cancel_info = $order_list_map[$val['orderId']];
                        $returnPriceCount += $cancel_info['deliverMoney'];
                    }
                    $refundGoodsCountNum = $val['goodsNums'];
                }
                $saleGoodsCount = $val['goodsNums'];
                $saleGoodsCountNum += $saleGoodsCount;
                if ($profitType == 1) {
                    //获取销售毛利列表
                    $goodsNums += $val['goodsNums'];
                    $marketNum += $val['goodsNums'];
                    $saleCount += $val['goodsNums'];
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
            $arr['shopName'] = $shopName;//店铺名称
            $arr['returnCount'] = $returnCount;
            $arr['returnPriceCount'] = formatAmountNum($returnPriceCount);//退货金额
            $arr['saleCount'] = $saleCount;//订单数量
            if ($profitType != 1) {
                $arr['saleCount'] = count($saleCountArr);
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
            $marketSum += $marketNum;//所有销售数量
            $refundGoodsSum += $refundGoodsCount;//所有退款商品数量
            $refundOrderSum += $returnCount;//所有退款订单数量
            $returnCostPriceSum += $returnCostPriceNum;//所有退款成本价
            if ($profitType == 1) {
                //获取销售毛利列表
//                $profitPercent = round($profit / $goodsPriceNum * 100) . "％";//毛利率
                $arr['orderNum'] = $orderNum;//订单数量
                $arr['goodsNums'] = $goodsNums;//商品数量
                $arr['marketNum'] = $marketNum;//销售数量
                $arr['refundOrderCount'] = $returnCount;//退款订单数量
                $arr['refundGoodsCount'] = $refundGoodsCount;//退款商品数量
//                $arr['profitSum'] = formatAmountNum($profit);//毛利
//                $arr['profitPercent'] = $profitPercent;//毛利率
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
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getFont()->setSize(13);
                //设置单元格内容水平居中
                $objPHPExcel->getActiveSheet()->getStyle($letter[$j] . $row)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
            }
            // 设置行高
            $objPHPExcel->getActiveSheet()->getRowDimension($row)->setRowHeight(25);
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
        $profitPercent = round($profit / $salePriceCount * 100) . "%";//毛利率
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
     * @return array|mixed
     * @throws \PHPExcel_Exception
     * 获取销售毛利列表
     */
    public function profitList($startDate, $endDate)
    {
        $type = (int)I('statType', 1);//1:按商品统计|2:按商品分类统计|3:按客户统计
        $res = array();
        if ($type == 1) {//按商品统计
            $res = $this->commoditySaleList($startDate, $endDate, $type = 1, $profitType = 1);
        }
        if ($type == 2) {//按商品分类统计
            $res = $this->commoditySaleList($startDate, $endDate, $type = 2, $profitType = 1);
        }
        if ($type == 3) {//按客户统计
            $res = $this->getUsersList($startDate, $endDate, $profitType = 1);
        }
        return $res;
    }

    /**
     * @param $goodsId
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 销售毛利列表--商品列表---获取商品详情
     */
    public function commodityProfitList($goodsId)
    {
        $res = $this->commoditySalesUserDetail($goodsId, $goodsCatId3 = 0, $profitType = 1);
        return $res;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     * 销售毛利---图表模式
     */
    public function profitStatData($startDate, $endDate)
    {
        $res = $this->CommoditySaleData($startDate, $endDate, $profitType = 1);
        return $res;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 客户毛利
     */
    public function userStatic($startDate, $endDate)
    {
        $res = $this->getUsersList($startDate, $endDate, $profitType = 1, $userType = 1);
        return $res;
    }

    /**
     * @param $userId
     * @return mixed
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 客户毛利----详情
     */
    public function userProfitStatList($userId)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);
        $where = [];
        $where['sor.userId'] = $userId;
        $where['sor.isPurchase'] = ['neq', 1];//是否采购[1:是|-1:否]
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }
        $field = "sor.*,wg.goodsName,s.shopName,s.shopSn";

        $reportModule = new ReportModule();
        $data = $reportModule->getReportSalesList($where, $field);


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
            $shopName = '';//店铺名称
            $shopSn = '';//店铺编码
            foreach ($v as $val) {
                if ($val['is_return_goods'] == 1) {//是否退款[1:是|-1:否]
                    $returnPriceCount += $val['refundFee'];
                    $refundGoodsCount += $val['goodsNums'];
                    $returnCostPriceNum += $val['goodsCostPrice'] * $refundGoodsCount;//商品退款成本价
                }
                $salePriceCount += $val['goodsPaidPrice'];
                $goodsName = $val['goodsName'];
                $shopSn = $val['shopSn'];
                $shopName = $val['shopName'];
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
            $arr['shopSn'] = $shopSn;//店铺名称
            $arr['shopName'] = $shopName;//店铺编码
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
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 采购汇总
     */
    public function purchaseStatDetail($startDate, $endDate)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $type = (int)I('purchaseType', 1);//1:按明细分类|2:按商品分类|3:按供应商分类
        $export = I('export', 0);//1:导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isPurchase'] = 1;//是否采购[1:是|-1:否]
        if (!empty(I('number'))) {//采购单号
            $where['wjp.number'] = I('number');
        }
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        $field = "wjp.number,sor.*,wg.goodsName,wjp.createTime,wg.goodsSn,wg.goodsDetail,wgc1.catname as goodsCatName1,wgc2.catname as goodsCatName2,wgc3.catname as goodsCatName3,wjs.account,wjr.supplierId,s.shopName";
        $data = M('sales_order_report sor')
            ->join('left join wst_shops s ON s.shopId = sor.shopId')
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
        $reportInfo = $reportModule->getOrderReportList($where, $fieldInfo);

        $res = [];
        $rest = [];
        foreach ($data as $v) {
            if (empty($v['supplierId'])) {
                $v['supplierId'] = 0;
            }
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
                    $account = $val['account'];//供应商
                    if (empty($val['supplierId'])) {
                        $account = "总仓";
                    }
                    $shopName = $val['shopName'];//店铺名称
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
                if ($type == 3) {
                    $arr['supplierName'] = $account;
                }
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
                    if ($type == 3) {
                        $arr['supplierName'] = $account;
                    }
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
        $list['procureOrderNum'] = $reportInfo[0]['procureOrderNum'];//采购单数量
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
     * @return mixed
     * 损耗概况
     */
    public function storeLossSummary($startDate, $endDate)
    {
        $where = [];
        $where['wor.reportDate'] = ['between', [$startDate, $endDate]];
        $fieldInfo = "sum(wor.procureOrderNum) as procureOrderNum,sum(wor.procureOrderMoney) as procureOrderMoney";
        $reportModule = new ReportModule();
        $reportInfo = $reportModule->getOrderReportList($where, $fieldInfo);
        return $reportInfo;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 库房损耗
     */
    public function storeLossOld($startDate, $endDate)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $endDate = date("Y-m-d", strtotime("$endDate +1 days"));
        $where = [];
        $where['i.createTime'] = ['between', [$startDate . ' 00:00:00', $endDate . ' 00:00:00']];
        $where['i.state'] = 2;
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        $reportInfo = M('inventory i')
            ->join('left join wst_shops s on s.shopId = i.shopId')
            ->where($where)
            ->select();
        $date = [];
        foreach ($reportInfo as $v) {
            $whereInfo = [];
            $whereInfo['wg.iid'] = $v['iid'];
            $whereInfo['wg.irFlag'] = 1;
            if (!empty((int)I('goodsCatId1'))) {
                $whereInfo['wg.goodsCatId1'] = I('goodsCatId1');
            }
            if (!empty((int)I('goodsCatId2'))) {
                $whereInfo['wg.goodsCatId2'] = I('goodsCatId2');
            }
            if (!empty((int)I('goodsCatId3'))) {
                $whereInfo['wg.goodsCatId3'] = I('goodsCatId3');
            }
            $field = "ir.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3,wg.goodsUnit";
            $res = M('inventory_report ir')
                ->join('left join wst_goods wg on wg.goodsId = ir.goodsId')
                ->join('left join wst_goods_cats wgc1 ON wgc1.catId = wg.goodsCatId1')
                ->join('left join wst_goods_cats wgc2 ON wgc2.catId = wg.goodsCatId2')
                ->join('left join wst_goods_cats wgc3 ON wgc3.catId = wg.goodsCatId3')
                ->where($whereInfo)
                ->field($field)
                ->select();
            foreach ($res as $val) {//按商品ID分组
                $date[$val['goodsId']][] = $val;
            }
        }
        $array = [];
        $numberSum = 0;//盘损总数
        $plateLossAmountSum = 0;//盘损总额
        foreach ($date as $v) {
            $arr = [];
            $number = 0;//盘损数量
            $plateLossAmount = 0;//盘损金额
            $goodsPriceSum = 0;//商品库存进货总金额
            foreach ($v as $val) {
                if ($val['oNumber'] != $val['nNumber']) {
                    $number += $val['oNumber'] - $val['nNumber'];//盘点前库存 - 盘点后库存
                    $goodsName = $val['goodsName'];
                    $goodsSpec = $val['goodsSpec'];
                    $goodsCatName1 = $val['goodsCatName1'];
                    $goodsCatName2 = $val['goodsCatName2'];
                    $goodsCatName3 = $val['goodsCatName3'];
                    $goodsUnit = $val['goodsUnit'];//现在被用于进货价
                    $plateLossAmount += $goodsUnit * $number;
                    $goodsPriceSum += $goodsUnit * $val['oNumber'];
                    $goodsSn = $val['goodsSn'];//商品编码

                    $arr['goodsName'] = $goodsName;
                    $arr['goodsSn'] = $goodsSn;
                    $arr['goodsSpec'] = $goodsSpec;
                    $arr['goodsCatName1'] = $goodsCatName1;
                    $arr['goodsCatName2'] = $goodsCatName2;
                    $arr['goodsCatName3'] = $goodsCatName3;
                    $arr['goodsCatName3'] = $goodsCatName3;
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

    public function storeLoss($startDate, $endDate)
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
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }
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
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 退货报损
     */
    public function returnLossOld($startDate, $endDate)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.isRefund'] = 1;//是否退款[1:是|-1:否]
        if (!empty((int)I('goodsCatId1'))) {
            $where['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $where['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $where['wg.goodsCatId3'] = I('goodsCatId3');
        }
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
        }

        $field = "wu.userName,sor.*,wg.goodsName,wg.goodsSn,wg.goodsSpec,wgc1.catName as goodsCatName1,wgc2.catName as goodsCatName2,wgc3.catName as goodsCatName3";

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
        $refundGoodsCountSum = 0;//退款商品数量总数
        $refundCostPriceSum = 0;//退款成本价总价
        $refundFeeSum = 0;//退款金额总价
        $goodsCostPricesSum = 0;//商品成本价总
        foreach ($res as $v) {
            $goodsNums = 0;//销售数量
            $goodsPaidPrice = 0;//商品实付金额
            $refundOrderCount = 0;//退款订单数量
            $refundGoodsCount = 0;//退款商品数量
            $refundFee = 0;//退款金额
            $refundCostPriceNum = 0;//退款成本价
            $refundFeeTotal = 0;//退款金额
            $goodsPriceNum = 0;//商品实际金额
            $goodsCostPriceNum = 0;//销售成本价
            $goodsCostPrice = 0;//进货价--成本价
            $goodsCostPrices = 0;//当前商品成本价
            foreach ($v as $val) {
                $refundFeeTotal += $val['refundFee'];
                $refundFee += $val['refundFee'];//退款金额
                $goodsPriceNum += $val['goodsNums'] * $val['goodsPrice'];//商品实际金额
                $refundOrderCount += 1;
                $refundGoodsCount += $val['goodsNums'];
                $goodsNums += $val['goodsNums'];
                $goodsPaidPrice += $val['goodsPaidPrice'];//商品实付金额
                //商品基本信息
                $goodsName = $val['goodsName'];
                $goodsSn = $val['goodsSn'];
                $goodsDesc = $val['goodsSpec'];
                $goodsCatName1 = $val['goodsCatName1'];
                $goodsCatName2 = $val['goodsCatName2'];
                $goodsCatName3 = $val['goodsCatName3'];

                $refundCostPriceNum += $goodsCostPrice * $refundGoodsCount;//退款成本价
                $goodsCostPrices += $val['goodsCostPrice'] * $val['goodsNums'];//当前商品成本价
            }
            $goodsPaidPriceSum += formatAmountNum($goodsPaidPrice);//销售实付金额总价
            $goodsNumSum += $goodsNums;//销售数量总数
            $goodsCostPriceSum += $goodsCostPriceNum;//销售成本价总价
            $refundCostPriceSum += $refundCostPriceNum;//退款成本价总价
            $refundGoodsCountSum += $refundGoodsCount;//退款商品数量总数
            $refundFeeSum += $refundFee;//退款商品金额总价
            $goodsCostPricesSum += $goodsCostPrices;//商品成本价总
            //商品信息
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

    public function returnLoss($startDate, $endDate)
    {
        $page = (int)I('page', 1);
        $pageSize = (int)I('pageSize', 15);
        $export = (int)I('export', 0);//导出
        $where = [];
        $where['sor.reportDate'] = ['between', [$startDate, $endDate]];
        $where['sor.is_return_goods'] = 1;//是否退货(-1:否|1:是)
        if (!empty((int)I('goodsCatId1'))) {
            $where['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $where['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $where['wg.goodsCatId3'] = I('goodsCatId3');
        }
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
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
     * @param $params
     * @return mixed
     * 获取综合统计
     * 1:全部(最多180天)|2:最近30天|3:最近90天|4:自定义时间
     */
    public function getComprehensiveReport($params)
    {
        $type = $params['type'];
        if ($type == 2) {
            $dayTime = getDateRules('lastThirtyDays');//最近30天
        } elseif ($type == 3) {
            $dayTime = getDateRules('lastNinetyDays');//最近90天
        } elseif ($type == 4) {
            $dateCode = $params['startDate'] . " - " . $params['endDate'];
            $dayTime = getDateRules($dateCode);//自定义时间
        }

        if ($type == 1) {

            //最近180天
//            $dayTime['startDate'] = date("Y-m-d", strtotime("-180 day")) . ' 00:00:00';
//
////            $dayTime['endDate'] = date("Y-m-d", time()) . ' 00:00:00';
//            $dayTime['endDate'] = date("Y-m-d", time()) . ' 23:59:59';

        }


        $rest = [];
        $rest['ordersStatistics'] = '';//订单统计【导出需要】
        //订单统计====================start================================================
        $ordersModule = new OrdersModule();
//        $orderData = $ordersModule->getOrderRealTotalMoney($dayTime['startDate'], $dayTime['endDate']);
        $orderData = $ordersModule->getOrderRealTotalMoneyNew($dayTime['startDate'], $dayTime['endDate']);
        $orderDataInfo = $orderData['data'];
//        $getOrderCount = $ordersModule->getOrderCount($dayTime['startDate'], $dayTime['endDate']);
        $getOrderCount = $ordersModule->getOrderCountNew($dayTime['startDate'], $dayTime['endDate']);
        $getOrderCountInfo = $getOrderCount['data'];
//        $rest['marketAllMoney'] = $orderData['data']['allMoney'];//销售总额
        $rest['marketAllMoney'] = $orderDataInfo['sale_order_amount_sum'];//销售总额
//        $rest['validOrdersCount'] = $getOrderCount['data']['userPayCount'];//有效订单总数
        $rest['validOrdersCount'] = $getOrderCountInfo['effective_order_count'];//有效订单总数
//        $rest['validOrdersMoney'] = $orderData['data']['realTotalMoney'];//有效订单总额
        $rest['validOrdersMoney'] = $orderDataInfo['effective_order_amount_sum'];//有效订单总额
//        $rest['invalidOrderCount'] = $getOrderCount['data']['invalidOrderCount'];//无效订单总数
        $rest['invalidOrderCount'] = $getOrderCountInfo['invalid_order_count'];//无效订单总数
//        $rest['invalidOrderMoney'] = $orderData['data']['invalidOrderMoney'];//无效订单总额
        $rest['invalidOrderMoney'] = $orderDataInfo['invalid_order_amount_sum'];//无效订单总额
//        $rest['orderTradedCount'] = $getOrderCount['data']['orderTradedCount'];//已成交订单总数
        $rest['orderTradedCount'] = $getOrderCountInfo['completed_order_count'];//已成交订单总数
//        $rest['orderTradedMoney'] = $orderData['data']['orderTradedMoney'];//已成交订单金额
        $rest['orderTradedMoney'] = $orderDataInfo['completed_order_amount_sum'];//已成交订单金额
//        $rest['orderAverageMoney'] = round($rest['validOrdersMoney'] / $rest['validOrdersCount'], 2);//订单均价
        $rest['orderAverageMoney'] = bc_math($rest['validOrdersMoney'], $rest['validOrdersCount'], 'bcdiv', 2);//订单均价
        //==============================end================================================
        $rest['usersStatistics'] = '';//会员统计【导出需要】
        //会员统计====================start================================================
        $usersModule = new UsersModule();
//        $usersData = $usersModule->getNewUsersCount($dayTime['startDate'], $dayTime['endDate']);
        $report_module = new ReportModule();
        $member_count_report = $report_module->memberCountReport($dayTime['startDate'], $dayTime['endDate']);
//        $rest['usersCount'] = $usersData['data'];//会员总数
//        $rest['userPayOrderCount'] = $getOrderCount['data']['userPayOrderCount'];//下单会员数
//        $rest['userOrderCount'] = $getOrderCount['data']['userPayCount'];//会员订单总数
//        $rest['userOrderMoney'] = $orderData['data']['realTotalMoney'];//会员购物总额
//        $rest['userBuyRate'] = round($rest['userPayOrderCount'] / $rest['usersCount'] * 100, 2) . "%";//会员购买率 下单会员数/会员总数 x 100%
//        $rest['userAverageOrder'] = ceil(round($rest['userOrderCount'] / $rest['usersCount'], 2));//会员平均订单数 会员订单总数/会员总数
//        $rest['userAverageOrderMoney'] = round($rest['userOrderMoney'] / $rest['userOrderCount'], 2);//会员平均购物额 会员购物总额/每会员订单数
//        $whereInfo = [];
//        if (!empty($dayTime['startDate']) and !empty($dayTime['endDate'])) {//根据回调时间来获取数据
//            $whereInfo['nlt.responseTime'] = ['between', [$dayTime['startDate'], $dayTime['endDate']]];
//        }
//        $whereInfo['nlt.notifyStatus'] = 1;
//        $notify_log_tab = M('notify_log');
//        $notify_log_list = $notify_log_tab->alias('nlt')
//            ->where($whereInfo)
//            ->field('nlt.id,nlt.requestJson,nlt.type,nlt.notifyStatus,nlt.requestTime')
//            ->select();
//        $set_meal_tab = M('set_meal');//套餐表
//        $coupon_set_tab = M('coupon_set');
//        $userWxMoney = 0;//用户微信支付的总金额
//        //数据类型(1:微信下单支付|2:微信重新支付|3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
//        foreach ($notify_log_list as $log) {
//            $wxPayMoney = 0;//初始化
//            $type = $log['type'];//type (3:微信余额充值|4:开通绿卡|5:优惠券购买(加量包))
//            $request_arr = json_decode($log['requestJson'], true);
//            if ($type == 1 || $type == 2) {//只有订单才有重新支付的情况,一对多的关系
//                $wxPayMoney = M('orders')->where(['isPay' => 1, 'orderToken' => $request_arr['orderToken']])->getField('realTotalMoney');
//            } elseif ($type == 3) {//微信余额充值
//                $wxPayMoney = (float)$request_arr['amount'];
//            } elseif ($type == 4) {//开通绿卡
//                $smId = (int)$request_arr['smId'];
//                $wxPayMoney = $set_meal_tab->where(['smId' => $smId])->getField('money');
//            } elseif ($type == 5) {//优惠券购买(加量包)
//                $csId = (int)$log['csId'];
//                $wxPayMoney = $coupon_set_tab->where(['csId' => $csId])->getField('nprice');
//            }
//            $userWxMoney += (float)$wxPayMoney;
//        }
//        $rest['userWxMoney'] = (float)$userWxMoney;//会员微信支付
        $rest['usersCount'] = $member_count_report['member_count'];
        $rest['userPayOrderCount'] = $member_count_report['paid_order_member_count'];
        $rest['userOrderCount'] = $member_count_report['member_order_count'];
        $rest['userOrderMoney'] = $member_count_report['member_order_amount'];
        $rest['userBuyRate'] = $member_count_report['member_buy_rate'];
        $rest['userAverageOrder'] = $member_count_report['member_avg_order'];
        $rest['userAverageOrderMoney'] = $member_count_report['member_avg_order_amount'];
        $rest['userWxMoney'] = $member_count_report['member_wx_pay_amount'];
        $rest['member_balance_pay_amount'] = $member_count_report['member_balance_pay_amount'];
        $rest['member_alipay_pay_amount'] = $member_count_report['member_alipay_pay_amount'];
        //==============================end================================================
        $rest['otherStatistics'] = '';//其他统计【导出需要】
        //其他统计====================start================================================
//        $orderList = $ordersModule->getOrderList($dayTime['startDate'], $dayTime['endDate']);
        $orderListData = $ordersModule->getOrderList($dayTime['startDate'], $dayTime['endDate']);
        $orderList = $orderListData['data'];
        $rest['orderScore'] = (string)array_sum(array_column($orderList, 'orderScore'));//订单赠送积分  所有已完成订单商品赠送的总积分
        $rest['couponMoney'] = (string)array_sum(array_column($orderList, 'couponMoney'));//消费优惠券金额 所有已完成订单使用的优惠券总金额
        $rest['deliverMoney'] = (string)array_sum(array_column($orderList, 'deliverMoney'));//总订单运费 所有已完成订单的总运费
        //==============================end================================================
        if (!empty(I('export'))) {
            $this->exportOrderComplainsList($rest, $dayTime);
        }
        return $rest;
    }

    /**
     * @param array $list
     * @param array $params
     * 导出综合统计数据
     */
    public function exportOrderComplainsList(array $list, array $params)
    {
        $lineName = [
            'ordersStatistics' => '订单统计',
            'marketAllMoney' => '销售总额',
            'validOrdersCount' => '有效订单总数',
            'validOrdersMoney' => '有效订单总额',
            'invalidOrderCount' => '无效订单总数(关闭或取消)',
            'invalidOrderMoney' => '无效订单总额',
            'orderTradedCount' => '已成交订单总数',
            'orderTradedMoney' => '已成交订单总额',
            'orderAverageMoney' => '订单均价',
            'usersStatistics' => '会员统计',
            'usersCount' => '会员总数',
            'userPayOrderCount' => '下单会员数',
            'userOrderCount' => '会员订单总数',
            'userOrderMoney' => '会员购物总额',
            'userBuyRate' => '会员购买率',
            'userAverageOrder' => '会员平均订单数',
            'userAverageOrderMoney' => '会员平均购物',
            'userWxMoney' => '会员微信支付',
            'member_balance_pay_amount' => '会员余额支付',
            'member_alipay_pay_amount' => '会员支付宝支付',
            'otherStatistics' => '其他统计',
            'orderScore' => '订单赠送积分',
            'couponMoney' => '消费优惠券金额',
            'deliverMoney' => '总订单运费'
        ];
        $title = ['ordersStatistics', 'usersStatistics', 'otherStatistics'];
        //拼接表格信息
        $date = '';
        $startDate = '';
        $endDate = '';
        if (!empty($params['startDate']) && !empty($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
            $date = $startDate . ' - ' . $endDate;
        }
        //794px  border-color:black;
        $body = "<style type=\"text/css\">
    table  {border-collapse:collapse;border-spacing:0;}
    td{font-family:Arial, sans-serif;font-size:14px;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;}
    th{font-family:Arial, sans-serif;font-size:14px;font-weight:normal;padding:10px 5px;border-style:solid;border-width:1px;overflow:hidden;word-break:normal;border-color:black;}
</style>";
        foreach ($lineName as $k => $v) {
            $th = "<th style='width:400px;'>$v</th>";
            if (in_array($k, $title)) {//小标题单独处理
                $colspan = 2;
                $th = "<td style='width:400px; height:40px; color:red;' font-size = '25px'  colspan = '{$colspan}'>$v</td>";
            }
            $body .= "<tr>" . "{$th}";
            foreach ($list as $key => $value) {
                if (in_array($key, $title)) {//过滤标题
                    continue;
                }
                if ($k == $key) {
                    $body .= "<th style='width:400px;'>" . $value . "</th>";
                }
            }
            $body .= "</tr>";
        }
        $headTitle = "综合统计" . date('Ymd_His');
        $filename = $headTitle . ".xls";
        usePublicExport($body, $headTitle, $filename, $date);
    }

    /**
     * @param $params
     * @return mixed
     * 获取综合订单统计
     * type 1:全部|2:最近30天|3:最近90天|4:自定义时间
     */
    public function getComprehensiveOrdersList($params)
    {
        $type = $params['type'];
        $sort = $params['sort'];
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        if ($type == 2) {
            $dayTime = getDateRules('lastThirtyDays');//最近30天
        } elseif ($type == 3) {
            $dayTime = getDateRules('lastNinetyDays');//最近90天
        } elseif ($type == 4) {
            $dateCode = $params['startDate'] . " - " . $params['endDate'];
            $dayTime = getDateRules($dateCode);//自定义时间
        }

        $ordersModule = new OrdersModule();
        $field = "o.orderId,o.orderNo,o.userName,o.userPhone,o.orderScore,o.deliverMoney,wc.couponMoney,o.pay_time,o.createTime,s.shopName";
        $orderList = $ordersModule->getOrderList($dayTime['startDate'], $dayTime['endDate'], $field, $sort);
        if (!empty($orderList['data'])) {
            foreach ($orderList['data'] as $k => $v) {
                $orderList['data'][$k]['couponMoney'] = (string)$v['couponMoney'];
                $orderList['data'][$k]['goodsNums'] = (int)M('order_goods')->where(['orderId' => $v['orderId']])->sum('goodsNums');
            }
        }
        $rest = arrayPage($orderList['data'], $page, $pageSize);
        return $rest;
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * 报损损耗
     */
    public function breakageLoss($startDate, $endDate)
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
        if (!empty((int)I('goodsCatId1'))) {
            $where['wg.goodsCatId1'] = I('goodsCatId1');
        }
        if (!empty((int)I('goodsCatId2'))) {
            $where['wg.goodsCatId2'] = I('goodsCatId2');
        }
        if (!empty((int)I('goodsCatId3'))) {
            $where['wg.goodsCatId3'] = I('goodsCatId3');
        }
        //店铺名称|编号
        if (!empty(I('shopWords'))) {
            $shopWords = I('shopWords');
            $maps['s.shopName'] = ['like', "%{$shopWords}%"];
            $maps['s.shopSn'] = ['like', "%{$shopWords}%"];
            $maps['_logic'] = 'OR';
            $where['_complex'] = $maps;
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