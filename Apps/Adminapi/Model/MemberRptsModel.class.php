<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 指定会员订单统计服务类
 */
class MemberRptsModel extends BaseModel {
    /**
     * @param $parameter
     * @return mixed
     * 会员消费记录
     */
	public function getOrders($parameter){
        $startDate = $parameter['startDate'];
        $endDate = $parameter['endDate'];
        $userName = $parameter['userName'];
        $userPhone = $parameter['userPhone'];
        $shopId = $parameter['shopId'];
        $orderStatus = I('orderStatus');
        if($orderStatus === ''){
            $orderStatus = 20;
        }
        $page = I('page',1,'intval');
        $pageSize = I('pageSize',15,'intval');
        //where
        $where = " where o.orderFlag=1 and u.userFlag=1 ";
        if($orderStatus != '20'){
            if($orderStatus==5){
                $where .=" and o.orderStatus in (-3,-4,-5,-6,-7) ";
            }elseif($orderStatus==7){
                $where .=" and o.orderStatus in (7,8,10) ";
            }elseif($orderStatus==8){
                $where .=" and o.orderStatus in (13,14) ";
            }else{
                $where .=" AND o.orderStatus = $orderStatus ";
            }
        }

        if(!empty($startDate)){
            $where .= " and o.createTime>='".$startDate."'";
        }
        if(!empty($endDate)){
            $where .= " and o.createTime<='".$endDate."'";
        }

        if(!empty($userName)){
            $where .= " and u.userName like '%".$userName."%' ";
        }
        if(!empty($userPhone)){
            $where .= " and u.userPhone like '%".$userPhone."%' ";
        }
        if(!empty($shopId)){
            $where .= " and o.shopId = '".$shopId."' ";
        }
        //店铺名称|编号
        if (!empty($parameter['shopWords'])) {
            $where .= " and (s.shopName like '%{$parameter['shopWords']}%' or s.shopSn like '%{$parameter['shopWords']}%') ";
        }
        $field = "o.*,u.loginName as buyer_loginName,u.userName as buyer_userName,u.userPhone as buyer_userPhone,s.shopName ";
        $sql = "select {$field} from __PREFIX__orders o 
                left join __PREFIX__users u on u.userId = o.userId 
                left join __PREFIX__shops s on s.shopId = o.shopId 
                ".$where;
        $sql.=" order by o.orderId desc ";
        $data = $this->pageQuery($sql,$page,$pageSize);
        return returnData($data);
	}
	
	//获取订单详情
	public function getOrderDetail($orderId,&$msg=''){
        $data = array();
        $sql = "SELECT * FROM __PREFIX__orders WHERE orderId = $orderId ";
        $order = $this->queryRow($sql);
        if(empty($order))return $data;
        $field = "og.orderId, og.goodsId ,g.goodsSn, og.goodsNums, og.goodsName , og.goodsPrice shopPrice,og.goodsThums,og.goodsAttrName,og.goodsAttrName,s.shopName";
        $sql = "select {$field} from __PREFIX__goods g 
                left join __PREFIX__order_goods og on og.orderId = {$orderId} 
                left join __PREFIX__shops s on s.shopId = g.shopId 
				WHERE g.goodsId = og.goodsId AND og.orderId = {$orderId}";
        $goods = $this->query($sql);
        $order["goodsList"] = $goods;
        return returnData($order);
	}
}