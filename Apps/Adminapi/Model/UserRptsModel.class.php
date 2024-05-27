<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 会员消费统计服务类
 */
class UserRptsModel extends BaseModel {
	/**
	 * 按月/日进行订单统计
	 */
	public function queryByMonthAndDays(){
		$statType = (int)I('statType');
	 	$startDate = date('Y-m-d H:i:s',strtotime(I('startDate')));
	 	$endDate = date('Y-m-d H:i:s',strtotime(I('endDate')));
	 	$areaId1 = (int)I('areaId1');
	 	$areaId2 = (int)I('areaId2');
	 	$areaId3 = (int)I('areaId3');
	 	$shopName = I('shopName');
	 	$where = ' ';
	 	if(!empty($shopName)){
	 	    $shopInfo = M('shops')->where("shopName='".$shopName."'")->field('shopId')->find();
	 	    $where .= " AND shopId='".$shopInfo['shopId']."'";
        }
	 	$rs = array();
	 	if($statType==0){
	 		/*$sql = "select left(createTime,10) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0
	 		        and createTime >='".$startDate." 00:00:00' and createTime<='".$endDate." 23:59:59'  ".$where;*/
            $sql = "select left(createTime,10) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0 
	 		        and createTime >='".$startDate."' and createTime<='".$endDate."'  ".$where;
	 		if($areaId1>0)$sql.=" and areaId1=".$areaId1;
	 		if($areaId2>0)$sql.=" and areaId2=".$areaId2;
	 		if($areaId3>0)$sql.=" and areaId3=".$areaId3;
	 		$sql.=" group by left(createTime,10)";

	 	}else{
	 		/*$sql = "select left(createTime,7) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0
	 		        and createTime >='".$startDate." 00:00:00' and createTime<='".$endDate." 23:59:59' ".$where;*/
            $sql = "select left(createTime,7) createTime,count(orderId) counts from __PREFIX__orders where orderStatus>=0
	 		        and createTime >='".$startDate."' and createTime<='".$endDate."' ".$where;
	 		if($areaId1>0)$sql.=" and areaId1=".$areaId1;
	 		if($areaId2>0)$sql.=" and areaId2=".$areaId2;
	 		if($areaId3>0)$sql.=" and areaId3=".$areaId3;
	 		$sql.="  group by left(createTime,7)";
	 	}
	 	$rs = $this->query($sql);
	 	$data = array('status'=>1);
	    foreach ($rs as $key =>$v){
	 		$data['list'][$v['createTime']]["o_0"] = $v['counts'];
	 	}
	 	return $data;
	}

    /**
     * 会员注册统计
     * 默认显示一周
     * startDate:Y-m-d
     * endDate:Y-m-d
     */
	public function userRegister(){
	    $result = array('code'=>0,'msg'=>'操作成功','data'=>array());
        $startDate = I('startDate',date('Y-m-d',strtotime('-6 days')));
        $endDate = I('endDate',date('Y-m-d'));
        $startDate = date('Y-m-d',strtotime($startDate));
        $endDate = date('Y-m-d',strtotime($endDate));
        $diff = WSTCompareDate($startDate,$endDate);
        $diff = abs($diff);
        $data = array();
        $m = M("users");
        for($i=0;$i<=$diff;$i++){
            $date = date('Y-m-d',strtotime($startDate)+$i*3600*24);
            $count = $m->where(array('createTime'=>array('like',$date.'%'),'userFlag'=>1))->count();
            $count = empty($count)?0:$count;
            $data[$date] = $count;
        }
        if (!empty($data)) $result['data'] = $data;
        return $result;
    }

    /**
     * 日活人数统计
     * 默认显示一周
     * startDate:Y-m-d
     * endDate:Y-m-d
     * loginSrc:登录来源，0:商城 1:webapp 2:App 3：小程序    -1:全部
     */
    public function dayActivity(){
        $result = array('code'=>0,'msg'=>'操作成功','data'=>array());
        $startDate = I('startDate',date('Y-m-d',strtotime('-6 days')));
        $endDate = I('endDate',date('Y-m-d'));
        $startDate = date('Y-m-d',strtotime($startDate));
        $endDate = date('Y-m-d',strtotime($endDate));
        $diff = WSTCompareDate($startDate,$endDate);
        $diff = abs($diff);
        $loginSrc = I('loginSrc',-1,'intval');
        $data = array();
        $m = M("log_user_logins");
        for($i=0;$i<=$diff;$i++){
            $date = date('Y-m-d',strtotime($startDate)+$i*3600*24);
            $condition = array('loginTime'=>array('like',$date.'%'));
            if ($loginSrc > -1) $condition['loginSrc'] = $loginSrc;
            $count = $m->where($condition)->count();
            $count = empty($count)?0:$count;
            $data[$date] = $count;
        }
        if (!empty($data)) $result['data'] = $data;
        return $result;
    }
};
?>