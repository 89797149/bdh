<?php
namespace Home\Model;
/**
 * 太旧了,懒得改,废弃
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 订单结算服务类
 */
class OrderSettlementsModel extends BaseModel {
    /**
     * 获取结算列表
     */
    public function querySettlementsByPage(){
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $parameter = I('');
        $settlementNo = WSTAddslashes($parameter['settlementNo']);
        $isFinish = (int)$parameter['isFinish'];
        $sql = "select * from __PREFIX__order_settlements where 1=1 and shopId='".$shopId."' ";
        if($settlementNo!='')$sql.=" and  settlementNo like '%".$settlementNo."%'";
        if($isFinish>-1 && isset($parameter['isFinish']))$sql.=" and isFinish=".$isFinish;
        $sql.=" order by settlementId desc";

        return  $this->pageQuery($sql,(int)I('page',1),I('pageSize',15));
    }
    /**
	  * 获取未结算列表[在线付款 && 用户已收货]
	  */
	public function queryUnSettlementOrdersByPage($parameter=array()){
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
        $shopId = $shopId?$shopId:$parameter['shopId'];
		$orderNo = WSTAddslashes(I('orderNo'));
		$userName = WSTAddslashes(I('userName'));
		$sql = "SELECT orderNo,orderId,userName,totalMoney,deliverMoney,realTotalMoney,poundageRate,poundageMoney,createTime FROM __PREFIX__orders o 
		    WHERE o.settlementId=0 and o.orderStatus=4 and o.payType=1 and o.shopId = $shopId ";
		$sql .= " and realTotalMoney>0 ";
		if($orderNo!='')$sql.=" and o.orderNo like '%".$orderNo."%'";
		if($userName!='')$sql.=" and o.userName like '%".$userName."%'";
		$sql.=" order by o.orderId desc";
		return  $this->pageQuery($sql,(int)I('page',1),I('pageSize',15));
	}
	/**
	 * 获取已结算订单列表
	 */
	public function querySettlementsOrdersByPage($parameter=array()){
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
        $shopId = $shopId?$shopId:$parameter['shopId'];
        $orderNo = WSTAddslashes(I('orderNo'));
		$settlementNo = WSTAddslashes(I('settlementNo'));
		$isFinish = (int)I('isFinish');
		$sql = "SELECT o.orderNo,o.couponId,o.orderId,o.userName,o.totalMoney,o.deliverMoney,o.realTotalMoney,os.settlementMoney,o.poundageRate,o.poundageMoney,o.createTime,os.settlementNo ,os.finishTime
		    FROM __PREFIX__orders o ,__PREFIX__order_settlements os
		    WHERE os.settlementId=o.settlementId and  o.settlementId>0 and o.orderStatus=4 and o.payType=1 and o.shopId = $shopId ";
		if($orderNo!='')$sql.=" and o.orderNo like '%".$orderNo."%'";
		if($settlementNo!='')$sql.=" and  os.settlementNo like '%".$settlementNo."%'";
		if($isFinish>-1)$sql.=" and os.isFinish=".$isFinish;
		$sql.=" order by o.settlementId desc";
		$rs = $this->pageQuery($sql,(int)I('page',1),(int)I('pageSize',15));
        #获取优惠券信息
        $couponsForId = array();
        if($rs['root']){
            $couponsList = array();
            $cid_arr = array_get_column($rs['root'],'couponId');
            if($cid_arr){
                $couponsList = D('Home/OrderSettlements')->getCouponsList($cid_arr);
            }
            $couponsForId = get_changearr_key($couponsList,'couponId');
        }
        if($couponsForId && is_array($couponsForId)){
            foreach ($rs['root'] as $key => &$value) {
                $value['subsidyMoney'] = (float)$couponsForId[$value['couponId']]['couponMoney'];
            }
        }
        #END
        return $rs;
	}
	/**
	 * 申请结算
	 */
	public function settlement($parameter=array()){
		$shopId = (int)session('WST_USER.shopId');
		if(empty($shopId)){
		    $shopId = $this->MemberVeri()['shopId'];
		}
        $shopId = $shopId?$shopId:$parameter['shopId'];
		$ids = WSTFormatIn(",", I('ids'));
		$sql = "select bankName,bankNo,bankUserName from __PREFIX__shops s 
		        left join __PREFIX__banks b on b.bankId=s.bankId where s.shopId=".$shopId;
		$accRs = $this->queryRow($sql);
		if(empty($accRs))return array('status'=>-1,'msg'=>'无效的结算账户!');
		$sql = "select orderId,orderNo,totalMoney,deliverMoney,realTotalMoney,poundageMoney,couponId from __PREFIX__orders where shopId=".$shopId." and orderId in(".$ids.") and settlementId=0 and orderStatus=4 and payType=1";
		$rs = $this->query($sql);
		if(empty($rs))return array('status'=>-1,'msg'=>'申请结算失败，请核对订单状态是否已申请结算了!');
		$orderMoney = 0;
		$settlementMoney = 0;
		$poundageMoney = 0;
		$subsidyMoney = 0;//补贴金额
		#获取优惠券信息
        $couponsList = array();
        $cid_arr = array_get_column($rs,'couponId');
        if($cid_arr){
            $couponsList = $this->getCouponsList($cid_arr);
        }
        $couponsForId = get_changearr_key($couponsList,'couponId');
        #END
        //分销金额
        $distributionMoney = 0;
        $userDistributionTab = M('user_distribution');
		foreach ($rs as $key =>$v){
			$orderMoney += $v['totalMoney']+$v['deliverMoney'];
			$settlementMoney +=($v['totalMoney']+$v['deliverMoney']-$v['poundageMoney']);
			$poundageMoney+=$v['poundageMoney'];
			//优惠券补贴
			if($v['couponId'] && isset($couponsForId[$v['couponId']]) && $couponsForId[$v['couponId']]){
                $coupons = $couponsForId[$v['couponId']];
                if($coupons['type'] == 1){
                    $subsidyMoney += (float)$coupons['couponMoney'];
                }
            }
			//END
            //分销金额
            $distributionMoney += $userDistributionTab->where("orderId='".$v['orderId']."'")->sum('distributionMoney');
		}
        $settlementStartMoney = floatval($GLOBALS['CONFIG']['settlementStartMoney']);
		if($settlementStartMoney>$orderMoney)return array('status'=>-1,'msg'=>'结算总金额必须大于'.$settlementStartMoney."才能申请结算!");
		//建立结算记录
		$data = array();
		$data['settlementType'] = 0;
		$data['shopId'] = $shopId;
		$data['accName'] = $accRs['bankName'];
		$data['accNo'] = $accRs['bankNo'];
		$data['accUser'] = $accRs['bankUserName'];
		$data['createTime'] = date('Y-m-d H:i:s');
		$data['orderMoney'] = $orderMoney;
		$data['settlementMoney'] = $settlementMoney - (float)$distributionMoney;
		$data['poundageMoney'] = $poundageMoney;
        $data['isFinish'] = 0;
        $data['subsidyMoney'] = $subsidyMoney;//补贴金额
        $data['distributionMoney'] = (float)$distributionMoney;//分销金额
		$settlementId = M('order_settlements')->add($data);
		if(false !== $settlementId){
			$sql = "update __PREFIX__order_settlements set settlementNo='".date('y').sprintf("%08d", $settlementId)."' where settlementId=".$settlementId;
			$this->execute($sql);
			foreach ($rs as $key =>$v){
				$sql = "update __PREFIX__orders set settlementId=".$settlementId." where orderId=".$v['orderId'];
				$this->execute($sql);
			}
			return array('status'=>1);
		}
		return array('status'=>1,'msg'=>'申请结算失败，请与管理员联系。');
	}

    /**
     * 获取优惠券列表
     */
    public function getCouponsList($ids=array()){
        if(!$ids || !is_array($ids)){
            return array();
        }
        $cwhere = array(
            'couponId' => array(
                'in',
                $ids
            ),
            'type' => 1,
        );
        $couponsList = M('coupons')->where($cwhere)->field('couponId,shopId,couponName,couponMoney,type')->select();
        return $couponsList;
    }

    /**
     * 根据取货码查询订单
     */
    public function queryCouponsOrders($parameter=array()){
        $shopId = (int)session('WST_USER.shopId');
        if(empty($shopId)){
            $shopId = $this->MemberVeri()['shopId'];
        }
        $shopId = $shopId?$shopId:$parameter['shopId'];
        $source = I('source');

        $where['source'] = $source;
        $where['shopId'] = $shopId;
        $data = M('user_self_goods')->where($where)->find();
        if($data){
            $where = array();
            $where['orderId'] = $data['orderId'];
            $where['isSelf'] = 1;
            $where['isPay'] = 1;
            $where['orderFlag'] = 1;
            $where['orderStatus'] = array('in','1,2,3');
            $resData = M('orders')->where($where)->find();
            $resData['user_self_goods_data'] = $data;
            return $resData;
        }
        return [];
    }
    
}
