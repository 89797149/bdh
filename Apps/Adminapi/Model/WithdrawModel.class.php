<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 提现服务类
 */
class WithdrawModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());

	 	//创建数据
	 	$id = I("id",0);
		$data = array();
		if($this->checkEmpty($data,true)){
			$data["shopId"] = I("shopId");
            $data["money"] = I("money");
			$data["state"] = I("state",0,'intval');
			$data["addTime"] = date('Y-m-d H:i:s');
			$data["updateTime"] = '';
            $data["wFlag"] = 1;
			$rs = M('withdraw')->add($data);

			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = (int)I('id',0);
	 	//修改数据
		$data = array();
		if($this->checkEmpty($data,true)){
            $data["state"] = I("state",0,'intval');
            $data["updateTime"] = date('Y-m-d H:i:s');
			$rs = M('withdraw')->where("id=".$id)->save($data);

			if(false !== $rs){
				$rd['code']= 0;
                $rd['msg'] = '操作成功';
			}
		}
		return $rd;
	 }

    /**
     * 获取套餐信息
     */
    public function getWithdraw(){
        return M('withdraw as w')->join('left join wst_shops as s on w.shopId = s.shopId')->field('w.*,s.shopName,s.shopCompany,s.shopTel')->where("w.id=".(int)I('id'))->find();
    }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
         $sql = "SELECT w.*,s.shopName,s.shopCompany,s.shopTel FROM __PREFIX__withdraw as w left join __PREFIX__shops as s on w.shopId = s.shopId WHERE w.wFlag=1 ";
         if(I('shopName')!='')$sql.=" and s.shopName LIKE '%".WSTAddslashes(I('shopName'))."%'";
         if(I('shopTel')!='')$sql.=" and s.shopTel LIKE '%".WSTAddslashes(I('shopTel'))."%'";
         $sql.="  ORDER BY w.addTime desc";
         $rs = $this->pageQuery($sql,$page,$pageSize);
         return $rs;
	 }
	  
	 /**
	  * 删除
	  */
	 public function del(){
	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
	 	$id = (int)I('id');
	 	$m = M('withdraw');
		$rs = $m->where("id=".$id)->save(array('wFlag'=>-1));
		if(false !== $rs){
		   $rd['code']= 0;
            $rd['msg'] = '操作成功';
		}
		
		return $rd;
	 }

};
?>