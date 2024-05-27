<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 拼团订单服务类
 */
class AssembleOrdersModel extends BaseModel {
	/**
	 * 获取订单详细信息
	 */
	 public function getDetail(){
	 	$id = (int)I('id',0);
		/*$sql = "select o.*,s.shopName from __PREFIX__orders o
	 	         left join __PREFIX__shops s on o.shopId=s.shopId 
	 	         where o.orderFlag=1 and o.orderId=".$id;*/
         $sql = "select o.*,s.shopName,o.userName,s.shopName,uar.state as assembleStatus,ac.title,ac.startTime,ac.endTime,ac.groupPeopleNum from __PREFIX__user_activity_relation uar left join __PREFIX__orders o on o.orderId=uar.orderId left join __PREFIX__assemble a on a.pid=uar.pid left join __PREFIX__users u on u.userId=o.userId left join __PREFIX__shops s on o.shopId=s.shopId left join __PREFIX__assemble_activity ac on ac.aid=uar.aid where o.orderId=' ".$id."'";
		$rs = $this->queryRow($sql);
		$config = $GLOBALS['CONFIG'];
         if($config['setDeliveryMoney'] == 2){
             if($rs['isPay'] == 1){
                 $rs['realTotalMoney'] = $rs['realTotalMoney']+$rs['deliverMoney'];
             }else{
                 if($rs['realTotalMoney'] < $config['deliveryFreeMoney']){
                     $rs['realTotalMoney'] = $rs['realTotalMoney']+$rs['setDeliveryMoney'];
                     $rs['deliverMoney'] = $rs['setDeliveryMoney'];
                 }
             }
         }
		//获取用户详细地址
		$sql = 'select communityName,a1.areaName areaName1,a2.areaName areaName2,a3.areaName areaName3 from __PREFIX__communitys c 
		        left join __PREFIX__areas a1 on a1.areaId=c.areaId1 
		        left join __PREFIX__areas a2 on a2.areaId=c.areaId2
		        left join __PREFIX__areas a3 on a3.areaId=c.areaId3
		        where c.communityId='.$rs['communityId'];
		$cRs = $this->queryRow($sql);
		$rs['userAddress'] = $cRs['areaName1'].$cRs['areaName2'].$cRs['areaName3'].$cRs['communityName'].$rs['userAddress'];
		//获取日志信息

		$sql = "select lo.*,u.loginName,u.userType,s.shopName from __PREFIX__log_orders lo
		         left join __PREFIX__users u on lo.logUserId = u.userId
		         left join __PREFIX__shops s on u.userType!=0 and s.userId=u.userId
		         where orderId=".$id;
		$rs['log'] = $this->query($sql);
		//获取相关商品
		$sql = "select og.*,g.goodsThums,g.goodsName,g.goodsId from __PREFIX__order_goods og
			        left join __PREFIX__goods g on og.goodsId=g.goodsId
			        where og.orderId = ".$id;
		$rs['goodslist'] = $this->query($sql);
		
		return $rs;
	 }

	 /**
	  * 订单分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
        $shopName = WSTAddslashes(I('shopName'));
     	$orderNo = WSTAddslashes(I('orderNo'));
     	$areaId1 = (int)I('areaId1',0);
     	$areaId2 = (int)I('areaId2',0);
     	$areaId3 = (int)I('areaId3',0);
     	$orderStatus = I('orderStatus',-9999);
         if($orderStatus === ''){
             $orderStatus = -9999;
         }
     	$assembleStatus = (int)I('assembleStatus',-9999);
     	$title = I('title');
     	$userPhone = I('userPhone');
     	$sql = "select o.orderId,o.orderNo,o.totalMoney,o.realTotalMoney,o.orderStatus,o.deliverMoney,o.payType,o.createTime,o.setDeliveryMoney,o.isPay,s.shopName,o.userName,uar.state as assembleStatus,ac.title,u.userPhone from __PREFIX__user_activity_relation uar left join __PREFIX__orders o on o.orderId=uar.orderId left join __PREFIX__assemble a on a.pid=uar.pid left join __PREFIX__users u on u.userId=o.userId left join __PREFIX__shops s on o.shopId=s.shopId left join __PREFIX__assemble_activity ac on ac.aid=uar.aid where o.orderFlag=1 ";
	 	if($areaId1>0)$sql.=" and s.areaId1=".$areaId1;
	 	if($areaId2>0)$sql.=" and s.areaId2=".$areaId2;
	 	if($areaId3>0)$sql.=" and s.areaId3=".$areaId3;
	 	if($shopName!='')$sql.=" and (s.shopName like '%".$shopName."%' or s.shopSn like '%".$shopName."%')";
	 	if($orderNo!='')$sql.=" and o.orderNo like '%".$orderNo."%' ";
	 	if($userPhone!='')$sql.=" and u.userPhone like '%".$userPhone."%' ";
	 	if($title!='')$sql.=" and ac.title like '%".$title."%' ";
	 	if($orderStatus!=-9999 && $orderStatus!=-100)$sql.=" and o.orderStatus=".$orderStatus;
	 	if($orderStatus==-100)$sql.=" and o.orderStatus in(-6,-7)";
	 	if($assembleStatus != -9999){
	 	    $sql .= " and uar.state='".$assembleStatus."' ";
        }
	 	$sql.=" order by o.orderId desc";
		$page = $this->pageQuery($sql,$page,$pageSize);
		//获取涉及的订单及商品
		if(count($page['root'])>0){
			$orderIds = array();
			foreach ($page['root'] as $key => $v){
				$orderIds[] = $v['orderId'];
			}
			$sql = "select og.orderId,og.goodsThums,og.goodsName,og.goodsId from __PREFIX__order_goods og
			        where og.orderId in(".implode(',',$orderIds).")";
		    $rs = $this->query($sql);
		    $goodslist = array();
		    foreach ($rs as $key => $v){
		    	$goodslist[$v['orderId']][] = $v;
		    }
		    $config = $GLOBALS['CONFIG'];
		    foreach ($page['root'] as $key => $v){
		    	$page['root'][$key]['goodslist'] = $goodslist[$v['orderId']];
                if($config['setDeliveryMoney'] == 2){
                    if($v['isPay'] == 1){
                        $page['root'][$key]['realTotalMoney'] = $v['realTotalMoney']+$v['deliverMoney'];
                    }else{
                        if($v['realTotalMoney'] < $config['deliveryFreeMoney']){
                            $page['root'][$key]['realTotalMoney'] = $v['realTotalMoney']+$v['setDeliveryMoney'];
                            $page['root'][$key]['deliverMoney'] = $v['setDeliveryMoney'];
                        }
                    }
                }
		    }
		}
		return $page;
	 }
}
?>