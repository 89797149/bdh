<?php
namespace Adminapi\Model;

/**
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
	public function queryByPage($page=1,$pageSize=15){
		$settlementNo = WSTAddslashes(I('settlementNo'));
		$isFinish = (int)I('isFinish',-1);
		$areaId1 = (int)I('areaId1');
		$areaId2 = (int)I('areaId2');
		$areaId3 = (int)I('areaId3');
		$sql = "select os.*,p.shopName from __PREFIX__order_settlements os, __PREFIX__shops p where os.shopId=p.shopId ";
		if($areaId1>0)$sql.=" and p.areaId1=".$areaId1;
		if($areaId2>0)$sql.=" and p.areaId2=".$areaId2;
		if($areaId3>0)$sql.=" and p.areaId3=".$areaId3;
		if($settlementNo!='')$sql.=" and settlementNo like '%".$settlementNo."%'";
		if($isFinish>-1)$sql.=" and isFinish=".$isFinish;
		$sql.=" order by settlementId desc";
		return  $this->pageQuery($sql,$page,$pageSize);
	}
	
	/**
	 * 获取订单结算信息
	 */
	public function get(){
		$id = (int)I('id');
		return $this->where('settlementId='.$id)->find();
	}
	/**
	 * 获取结算详情
	 */
	public function getDetail(){
		$id = (int)I('id');
		$sql = "select os.*,p.shopName from __PREFIX__order_settlements os,__PREFIX__shops p where os.shopId=p.shopId and os.settlementId=".$id;
		$rs =  $this->queryRow($sql);
		//获取订单列表
		$sql = "select orderId,orderNo,userName,realTotalMoney,poundageRate,poundageMoney,couponId from __PREFIX__orders where settlementId=".$id;
		$rs['List'] = $this->query($sql);
        #获取优惠券信息
        $couponsForId = array();
		if($rs['List']){
            $couponsList = array();
            $cid_arr = array_get_column($rs['List'],'couponId');
            if($cid_arr){
                $couponsList = D('Home/OrderSettlements')->getCouponsList($cid_arr);
            }
            $couponsForId = get_changearr_key($couponsList,'couponId');
        }
		if($couponsForId && is_array($couponsForId)){
		    foreach ($rs['List'] as $key => &$value) {
                $value['subsidyMoney'] = (float)$couponsForId[$value['couponId']]['couponMoney'];
		    }
        }
		#END
		return $rs;
	}

    /**
     * 结算
     */
    public function settlement(){
        $id = (int)I('id');
        $rd = array('status'=>-1,'msg'=>'结算失败');
        $rs = $this->where('isFinish=0 and settlementId='.$id)->find();
        if($rs['settlementId']!=''){
            $data = array();
            $data['isFinish'] = 1;
            $data['finishTime'] = date('Y-m-d H:i:s');
            $data['remarks'] = I('content');
            if(I('autoReturn') == 1){
                //自动退款
                $m = D('Admin/Orders');

                $payData['amount'] = $rs['settlementMoney'];
                $payData['orderNo'] = $rs['settlementNo'];
                $shopInfo = M('shops')->where("shopId='".$rs['shopId']."'")->find();
                $userInfo = M('users')->where("userId='".$shopInfo['userId']."'")->find();
                if(empty($userInfo['openId'])){
                    $rd['msg'] = '该用户缺少openid';
                    return $rd;
                }
                $payData['openid'] = $userInfo['openId'];
                $result = $m->wxPayTransfers($payData);
                M()->startTrans();//开启事物 注意数据表是否开启Innodb
                $rss = $this->where("settlementId=".$id)->save($data);
                if($result['result_code'] == 'SUCCESS' && $rss !== false){
                    M()->commit();
                    $rd['status'] = 1;
                    $rd['msg'] = '结算成功';
                }else{
                    M()->rollback();
                }
                return $rd;
            }
            $rss = $this->where("settlementId=".$id)->save($data);
            if(false !== $rss){
                $rd['status']= 1;
            }
        }
        return $rd;
    }
}