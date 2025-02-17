<?php
namespace Home\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 会员服务类
 */
class UserScoreModel extends BaseModel {
	
     /**
	  * 获取用户积分列表
	  */
     public function getScoreList(){
		$scoreType = (int)I("scoreType",0);
	 	$userId=(int)session('WST_USER.userId');
	 	$sql = "select us.scoreId,us.userId,us.score,us.dataSrc,us.dataId,us.scoreType,us.createTime,us.dataRemarks,o.orderNo from __PREFIX__user_score us, __PREFIX__orders o  
	 			where us.dataId=o.orderId and us.userId=".$userId;
	 	if($scoreType>0){
	 		$sql.=" and us.scoreType= $scoreType";
	 	}
	 	$sql.=" order by us.createTime desc ";
	 	$rs = $this->pageQuery($sql);
	 	return $rs;
	 }

    /**
     * 会员-获取用户积分列表
     */
    public function getScoreListForPos($userId,$page){
        $scoreType = (int)I("scoreType",0);
        $sql = "select us.scoreId,us.userId,us.score,us.dataSrc,us.dataId,us.scoreType,us.createTime,us.dataRemarks,po.orderNO from __PREFIX__user_score us, __PREFIX__pos_orders po
	 			where us.dataId=po.id and us.userId=".$userId;
        if($scoreType>0){
            $sql.=" and us.scoreType= $scoreType";
        }
        $sql.=" order by us.createTime desc ";
        $rs = $this->pageQuery($sql,$page['page'], $page['pageSize']);
        return $rs;
    }
	
}