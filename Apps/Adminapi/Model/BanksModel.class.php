<?php
 namespace Adminapi\Model;
/**
 * ============================================================================
 * NiaoCMS商城
 * 官网地址:http://www.niaocms.com 
 * 联系QQ:1692136178
 * ============================================================================
 * 银行服务类
 */
class BanksModel extends BaseModel {
    /**
	  * 新增
	  */
	 public function insert(){
//	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
		$data = array();
		$data["bankName"] = I("bankName");
		$data["bankFlag"] = 1;
		if($this->checkEmpty($data,true)){
			$rs = $this->add($data);
			if(false !== $rs){
//				$rd['code']= 0;
//                $rd['msg'] = '操作成功';
                $rd = returnData(true,0,'success','操作成功');
			}
		}
		return $rd;
	 } 
     /**
	  * 修改
	  */
	 public function edit(){
//	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
	 	$id = (int)I("id",0);
		$data["bankId"] = (int)I("id");
		$data["bankName"] = I("bankName");
		if($this->checkEmpty($data)){	
			$rs = $this->where("bankId=".(int)I('id'))->save($data);
			if(false !== $rs){
//				$rd['code']= 0;
//                $rd['msg'] = '操作成功';
                $rd = returnData(true,0,'success','操作成功');
			}
		}
		return $rd;
	 } 
	 /**
	  * 获取指定对象
	  */
     public function get(){
		return (array)$this->where("bankId=".(int)I('id'))->find();
	 }
	 /**
	  * 分页列表
	  */
     public function queryByPage($page=1,$pageSize=15){
	 	$sql = "select * from __PREFIX__banks where bankFlag=1 order by bankId desc";
		$rs = $this->pageQuery($sql,$page,$pageSize);
		return $rs;
	 }
	 /**
	  * 获取列表
	  */
	  public function queryByList(){
		 $rs = (array)$this->where('bankFlag=1')->select();
		 return $rs;
	  }
	  
	 /**
	  * 删除
	  */
	 public function del(){
//	 	$rd = array('code'=>-1,'msg'=>'操作失败','data'=>array());
         $rd = returnData(false, -1, 'error', '操作失败');
		$rs = $this->delete((int)I('id'));
		if($rs){
//		   $rd['code']= 0;
//            $rd['msg'] = '操作成功';
            $rd = returnData(true,0,'success','操作成功');
		}
		return $rd;
	 }
};
?>