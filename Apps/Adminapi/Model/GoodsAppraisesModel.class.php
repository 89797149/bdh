<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 商品评价服务类
 */
class GoodsAppraisesModel extends BaseModel {
     /**
	  * 修改
	  */
	 public function edit(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = (int)I("id",0);
		$m = M('goods_appraises');
		$data["goodsScore"] = I("goodsScore");
		$data["serviceScore"] = I("serviceScore");
		$data["timeScore"] = I("timeScore");
		$data["content"] = I("content");
		$data["isShow"] = (int)I("isShow",1);
		
		
		$scro = (int)$data["goodsScore"]+(int)$data["serviceScore"]+(int)$data["timeScore"];
		switch ($scro)
			{
			case $scro <=5 :
				$data["compScore"] = 0;

			  break;  
			case $scro > 5 and  $scro <= 10:
			  $data["compScore"] = 1;

			  break;
			case $scro > 10 and  $scro <= 15:
			 $data["compScore"] = 2;

			  break;
			default:
				$data["compScore"]=null;
			}
		
		
		if($this->checkEmpty($data)){	
			$rs = $m->where("id=".$id)->save($data);
			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 } 
	 /**
	  * 获取指定对象
	  */
     public function get(){
	 	$id = (int)I('id');
	 	$field = " gp.*,o.orderNo,u.loginName,g.goodsName,g.goodsThums,p.shopName ";
		$sql = "select {$field} from __PREFIX__goods_appraises gp 
		         left join __PREFIX__goods g on gp.goodsId = g.goodsId
		         left join __PREFIX__orders o on gp.orderId = o.orderId 
		         left join __PREFIX__shops p on p.shopId = gp.shopId 
		         left join __PREFIX__users u on u.userId = gp.userId 
		         where gp.id = ".$id;
         $res = $this->queryRow($sql);
         if(!empty($res)){
             if(!empty($res['appraisesAnnex'])){
                 $res['appraisesAnnex'] = explode(',',$res['appraisesAnnex']);
             }else{
                 $res['appraisesAnnex'] = [];
             }
         }
         return returnData($res, 0, 'success', '成功');
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
     	$shopName = WSTAddslashes(I('shopName'));
     	$goodsName = WSTAddslashes(I('goodsName'));
     	$areaId1 = (int)I('areaId1',0);
     	$areaId2 = (int)I('areaId2',0);
	 	$sql = "select gp.*,g.goodsName,g.goodsThums,o.orderNo,u.loginName,p.shopName from __PREFIX__goods_appraises gp
	 	         left join __PREFIX__goods g on gp.goodsId = g.goodsId
		         left join __PREFIX__orders o on gp.orderId = o.orderId 
		         left join __PREFIX__users u on u.userId = gp.userId 
		         left join __PREFIX__shops p on p.shopId = gp.shopId 
	 	        where p.shopId = g.shopId and gp.goodsId = g.goodsId and o.orderId = gp.orderId ";
	 	if($areaId1>0)$sql.=" and p.areaId1=".$areaId1;
	 	if($areaId2>0)$sql.=" and p.areaId2=".$areaId2;
	 	if($shopName!='')$sql.=" and (p.shopName like '%".$shopName."%' or p.shopSn like '%'".$shopName."%')";
	 	if($goodsName!='')$sql.=" and (g.goodsName like '%".$goodsName."%' or g.goodsSn like '%".$goodsName."%')";
	 	$sql.="  order by id desc";
		$rs = $this->pageQuery($sql,$page,$pageSize);
         if(!empty($rs['root'])){
             foreach ($rs['root'] as $k=>$v){
                 if(!empty($v['appraisesAnnex'])){
                     $rs['root'][$k]['appraisesAnnex'] = explode(',',$v['appraisesAnnex']);
                 }else{
                     $rs['root'][$k]['appraisesAnnex'] = [];
                 }
             }
         }
		return $rs;
	 }
	  
	 /**
	  * 删除
	  */
	 public function del(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
		$rs = $this->delete((int)I('id'));
		if($rs){
		   $rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		return $rd;
	 }
};
?>