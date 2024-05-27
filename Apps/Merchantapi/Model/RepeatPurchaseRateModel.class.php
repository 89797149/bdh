<?php
 namespace Merchantapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 复购率报表
 */
class RepeatPurchaseRateModel extends BaseModel {

    /**
     * 复购率报表统计
     */
    public function repeatPurchaseRate($shopId){
        $result = array('code'=>0,'msg'=>'操作成功','data'=>array());
        $dateType = I('dateType',0,'intval');//日期方式，0：全部，1：天 2：周 3：月 4：季 5：年
        $where = " o.orderStatus = 4 and o.orderFlag = 1 and o.shopId = " . $shopId;
        if ($dateType == 1){//天
            $where .= " and o.createTime >= '" . date('Y-m-d') . " 00:00:00' and o.createTime <= '" . date('Y-m-d') . " 23:59:59' ";
        } else if($dateType == 2){//周
            $where .= " and o.createTime >= '" . date('Y-m-d',strtotime("+1 days",strtotime(date('Y-m-d',strtotime('-1 weeks'))))) . " 00:00:00' and o.createTime <= '" . date('Y-m-d') . " 23:59:59' ";
        } else if($dateType == 3){//月
            $where .= " and o.createTime >= '" . date('Y-m-d',strtotime("+1 days",strtotime(date('Y-m-d',strtotime('-1 months'))))) . " 00:00:00' and o.createTime <= '" . date('Y-m-d') . " 23:59:59' ";
        } else if($dateType == 4){//季
            $where .= " and o.createTime >= '" . date('Y-m-d',strtotime("+1 days",strtotime(date('Y-m-d',strtotime('-3 months'))))) . " 00:00:00' and o.createTime <= '" . date('Y-m-d') . " 23:59:59' ";
        } else if($dateType == 5){//年
            $where .= " and o.createTime >= '" . date('Y-m-d',strtotime("+1 days",strtotime(date('Y-m-d',strtotime('-1 years'))))) . " 00:00:00' and o.createTime <= '" . date('Y-m-d') . " 23:59:59' ";
        }
        $startDate = I('startDate');
        $endDate = I('endDate');
        if ($startDate > '0000-00-00 00:00:00'){
            $where .= " and o.createTime >= '" . $startDate . "' ";
        }
        if ($endDate > '0000-00-00 00:00:00'){
            $where .= " and o.createTime <= '" . $endDate . "' ";
        }
        $shopCatId1 = I('shopCatId1',0,'intval');
        if ($shopCatId1 > 0){
            $where .= " and g.shopCatId1 = " . $shopCatId1;
        }
        $goodsId = I('goodsId',0,'intval');
        if ($goodsId > 0){
            $where .= " and og.goodsId = " . $goodsId;
        }
        $order_goods_list = M('order_goods as og')->field('og.goodsId,count(og.goodsId) as counts')->join('left join wst_orders as o on o.orderId = og.orderId')->join('left join wst_goods as g on og.goodsId = g.goodsId')->where($where)->group('og.goodsId')->select();
        $counts_2 = 0;
        $counts_3 = 0;
        $counts_4 = 0;
        $counts_5 = 0;
        if(!empty($order_goods_list)){
            foreach($order_goods_list as $v){
                if ($v['counts'] == 2){
                    $counts_2 += 1;
                } else if($v['counts'] == 3){
                    $counts_3 += 1;
                } else if($v['counts'] == 4){
                    $counts_4 += 1;
                } else if($v['counts'] >= 5){
                    $counts_5 += 1;
                }
            }
        }
        $result['data'] = array(
            '0' =>  array(
                'name'  =>  '2次购买人数',
                'nums'  =>  $counts_2
            ),
            '1' =>  array(
                'name'  =>  '3次购买人数',
                'nums'  =>  $counts_3
            ),
            '2' =>  array(
                'name'  =>  '4次购买人数',
                'nums'  =>  $counts_4
            ),
            '3' =>  array(
                'name'  =>  '5次及以上购买人数',
                'nums'  =>  $counts_5
            ),
        );
        return $result;
    }
};
?>