<?php

namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com
 * 联系QQ:1692136178
 * ============================================================================
 * 订单统计服务类
 */
class OrderRptsModel extends BaseModel
{
    /**
     * 按月/日进行订单统计
     */
    public function queryByMonthAndDays()
    {
        $statType = (int)I('statType');
        $startDate = date('Y-m-d H:i:s', strtotime(I('startDate')));
        $endDate = date('Y-m-d H:i:s', strtotime(I('endDate')));
        $areaId1 = (int)I('areaId1');
        $areaId2 = (int)I('areaId2');
        $areaId3 = (int)I('areaId3');
        $shopName = I('shopName');
        $where = ' ';
        if (!empty($shopName)) {
            $shopInfo = M('shops')->where("shopName='" . $shopName . "'")->field('shopId')->find();
            $where .= " AND shopId='" . $shopInfo['shopId'] . "'";
        }
        $rs = array();
        if ($statType == 0) {
            /*$sql = "select left(createTime,10) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0
                    and createTime >='".$startDate." 00:00:00' and createTime<='".$endDate." 23:59:59'  ".$where;*/
            $sql = "select left(createTime,10) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0 
	 		        and createTime >='" . $startDate . "' and createTime<='" . $endDate . "'  " . $where;
            if ($areaId1 > 0) $sql .= " and areaId1=" . $areaId1;
            if ($areaId2 > 0) $sql .= " and areaId2=" . $areaId2;
            if ($areaId3 > 0) $sql .= " and areaId3=" . $areaId3;
            $sql .= " group by left(createTime,10)";

        } else {
            /*$sql = "select left(createTime,7) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0
                    and createTime >='".$startDate." 00:00:00' and createTime<='".$endDate." 23:59:59' ".$where;*/
            $sql = "select left(createTime,7) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0
	 		        and createTime >='" . $startDate . "' and createTime<='" . $endDate . "' " . $where;
            if ($areaId1 > 0) $sql .= " and areaId1=" . $areaId1;
            if ($areaId2 > 0) $sql .= " and areaId2=" . $areaId2;
            if ($areaId3 > 0) $sql .= " and areaId3=" . $areaId3;
            $sql .= "  group by left(createTime,7)";
        }
        $rs = $this->query($sql);
        $data = array('code' => 0, 'msg' => '操作成功', 'data' => array());
        foreach ($rs as $key => $v) {
            $data['data'][$v['createTime']]["o_0"] = $v['counts'];
        }
        return $data;
    }

    /**
     * @param $params
     * @return array
     * 退款日志记录
     */
    public function refundFee($params)
    {
        $page = $params['page'];
        $pageSize = $params['pageSize'];
        $userName = I('userName');
        $userPhone = I('userPhone');
        $orderNo = I('orderNo');
        $startDate = I('startDate', date('Y-m-d ', strtotime("-1 day"))); // 去除秒
        $startDate .= "00:00:00";
        $endDate = I('endDate', date('Y-m-d ') . '23:59:59'); //去除秒
        if (!empty(I('startDate'))) {
            if (count(explode('-', I('startDate'))) > 1) {
                $startDate = I('startDate');
            } else {
                $startDate = date('Y-m-d H:i:s', I('startDate'));
            }
        }
        if (!empty(I('endDate'))) {
            if (count(explode('-', I('endDate'))) > 1) {
                $endDate = I('endDate');
            } else {
                $endDate = date('Y-m-d H:i:s', I('endDate'));
            }
        }
        $where = " where 1=1 ";
        if (!empty($userPhone)) {
            $where .= " and u.userPhone like '%" . $userPhone . "%' ";
        }
        if (!empty($userName)) {
            $where .= " and u.userName ='" . $userName . "' ";
        }
        if (!empty($orderNo)) {
            $where .= " and o.orderNo ='" . $orderNo . "' ";
        }
        if (!empty($startDate) && !empty($endDate)) {
            $where .= " and oc.addTime between '" . $startDate . "' and '" . $endDate . "' ";
        }
        $field = "oc.*,o.orderNo,u.userName,u.userPhone,s.shopName ";
        $sql = "select {$field} from __PREFIX__order_complainsrecord oc 
                left join __PREFIX__orders o on o.orderId = oc.orderId 
                left join __PREFIX__shops s on s.shopId = o.shopId 
                left join __PREFIX__users u on u.userId = o.userId $where";
        $sql .= "order by oc.addTime desc ";
        $list = $this->pageQuery($sql, $page, $pageSize);
        $list['totalMoney'] = '0.00';
        $sql = "select sum(oc.money) from __PREFIX__order_complainsrecord oc left join __PREFIX__orders o on o.orderId=oc.orderId left join __PREFIX__users u on u.userId = o.userId $where";
        $totalMoney = $this->queryRow($sql)['sum(oc.money)'];
        if ($totalMoney > 0) {
            $list['totalMoney'] = $totalMoney;
        }
        return returnData($list);
    }

    /**
     * 订单转化率统计
     * 默认显示一周
     * startDate:Y-m-d
     * endDate:Y-m-d
     */
    public function orderConversionRate()
    {
        $result = array('code' => 0, 'msg' => '操作成功', 'data' => array());
        $startDate = I('startDate', date('Y-m-d', strtotime('-6 days')));
        $endDate = I('endDate', date('Y-m-d'));
        $startDate = date('Y-m-d', strtotime($startDate));
        $endDate = date('Y-m-d', strtotime($endDate));
        $diff = WSTCompareDate($startDate, $endDate);
        $diff = abs($diff);
        $shopId = I('shopId', 0, 'intval');
        $data = array();
        $m = M("orders");
        for ($i = 0; $i <= $diff; $i++) {
            $date = date('Y-m-d', strtotime($startDate) + $i * 3600 * 24);
            $condition = array('createTime' => array('like', $date . '%'), 'orderFlag' => 1);
            if ($shopId > 0) $condition['shopId'] = $shopId;
            $total_order_count = $m->where($condition)->count();
            $total_order_count = empty($total_order_count) ? 0 : $total_order_count;

            $condition['orderStatus'] = 4;
            $complete_order_count = $m->where($condition)->count();
            $complete_order_count = empty($complete_order_count) ? 0 : $complete_order_count;

            $rate = $complete_order_count / $total_order_count;
            if ($rate == 1) {
                $data[$date] = '100.00';
            } else {
                $orderConversionRate = number_format($complete_order_count / $total_order_count, 2);
                $data[$date] = $orderConversionRate;
            }
        }
        if (!empty($data)) $result['data'] = $data;
        return $result;
    }
}

;
?>